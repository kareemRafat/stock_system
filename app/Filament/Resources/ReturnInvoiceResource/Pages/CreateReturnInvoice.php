<?php

namespace App\Filament\Resources\ReturnInvoiceResource\Pages;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Validator;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ReturnInvoiceResource;
use Illuminate\Database\Eloquent\Model; // Add this import

class CreateReturnInvoice extends CreateRecord
{
    protected static string $resource = ReturnInvoiceResource::class;

    public ?int $originalInvoiceId = null;

    public function mount(): void
    {
        parent::mount();

        $this->originalInvoiceId = request()->query('original_invoice');

        // Generate return number once
        $time = (int)(microtime(true) * 1000000);
        $sixDigits = $time % 1000000;
        $returnNumber = 'RET-' . str_pad($sixDigits, 6, '0', STR_PAD_LEFT);

        if ($this->originalInvoiceId) {
            $originalInvoice = Invoice::with('items.product')->find($this->originalInvoiceId);

            if ($originalInvoice) {
                // هات كل المرتجعات المرتبطة بالفاتورة الأصلية مرة واحدة
                $returns = \App\Models\ReturnInvoiceItem::whereHas('returnInvoice', function ($q) use ($originalInvoice) {
                    $q->where('original_invoice_id', $originalInvoice->id);
                })
                    ->selectRaw('product_id, SUM(quantity_returned) as total_returned')
                    ->groupBy('product_id')
                    ->pluck('total_returned', 'product_id');
                // هترجع مصفوفة [product_id => total_returned]

                // جهّز بيانات الأصناف مع استبعاد اللي خلصت (remaining <= 0)
                $itemsData = $originalInvoice->items
                    ->map(function ($item) use ($returns) {
                        $totalReturned = $returns[$item->product_id] ?? 0;
                        $remaining = $item->quantity - $totalReturned;

                        if ($remaining <= 0) {
                            return null; // تجاهل الصنف اللي خلص
                        }

                        return [
                            'product_id' => $item->product_id,
                            'quantity' => $remaining,
                            'quantity_returned' => 0,
                        ];
                    })
                    ->filter()   // شيل الـ null
                    ->values()   // رتب العناصر من الأول
                    ->toArray();

                // املأ الفورم
                $this->form->fill([
                    'customer_id' => $originalInvoice->customer_id,
                    'original_invoice_number' => $originalInvoice->invoice_number,
                    'original_invoice_id' => $originalInvoice->id,
                    'return_invoice_number' => $returnNumber,
                    'notes' => $originalInvoice->notes,
                    'items' => $itemsData,
                ]);
            }
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Extract items data
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Validate and filter items
        $validItems = $this->getValidReturnItems($items);

        if (empty($validItems)) {
            $this->showNoItemsNotification();
            $this->halt();
        }

        // Calculate total amount
        $totalAmount = $this->calculateTotalAmount($validItems);
        $data['total_amount'] = $totalAmount;

        // Create return invoice with items
        return $this->createReturnInvoiceWithItems($data, $validItems);
    }

    /**
     * Filter out items that shouldn't be returned
     */
    private function getValidReturnItems(array $items): array
    {
        return array_filter($items, function ($item) {
            return (!empty($item['return_all']) && $item['return_all']) ||
                (!empty($item['quantity_returned']) && $item['quantity_returned'] > 0);
        });
    }

    /**
     * Show notification when no items are selected for return
     */
    private function showNoItemsNotification(): void
    {
        Notification::make()
            ->title('لم يتم اختيار اي سلعة للإرجاع')
            ->body('يجب اختيار منتجات او كمية للإرجاع')
            ->danger()
            ->send();
    }

    /**
     * Calculate total amount for valid return items
     */
    private function calculateTotalAmount(array $validItems): float
    {
        $totalAmount = 0;

        foreach ($validItems as $item) {
            $quantityReturned = $this->getQuantityReturned($item);
            $price = $this->getProductPrice($item['product_id']);
            $totalAmount += $quantityReturned * $price;
        }

        return $totalAmount;
    }

    /**
     * Get the quantity to be returned for an item
     */
    private function getQuantityReturned(array $item): int
    {
        if (!empty($item['return_all']) && $item['return_all']) {
            return $item['quantity'];
        }

        return $item['quantity_returned'] ?? 0;
    }

    /**
     * Get product price by ID
     */
    private function getProductPrice($productId): float
    {
        $product = Product::find($productId);
        return $product->price ?? 0;
    }

    /**
     * Create return invoice and its items in a transaction
     */
    private function createReturnInvoiceWithItems(array $data, array $validItems): Model
    {
        return DB::transaction(function () use ($data, $validItems) {
            // Create the return invoice
            $returnInvoice = static::getModel()::create($data);

            foreach ($validItems as $item) {
                $this->createReturnInvoiceItem($returnInvoice, $item);
            }

            return $returnInvoice;
        });
    }

    /**
     * Create a single return invoice item and update inventory
     */
    private function createReturnInvoiceItem(Model $returnInvoice, array $item): void
    {
        $quantityReturned = $this->getQuantityReturned($item);

        // Safety check (though items are already validated)
        if ($quantityReturned <= 0) {
            return;
        }

        $product = Product::find($item['product_id']);
        $price = $this->getProductPrice($item['product_id']);
        $subtotal = $quantityReturned * $price;

        $returnInvoice->items()->create([
            'product_id' => $item['product_id'],
            'quantity_returned' => $quantityReturned,
            'price' => $price,
            'subtotal' => $subtotal,
        ]);

        // Update inventory: add the returned quantity back to stock
        if ($product) {
            $product->increment('stock_quantity', $quantityReturned);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }

    // to remove add and add more
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(), // زر إضافة فقط
            $this->getCancelFormAction(), // زر إلغاء يرجع للـ index
        ];
    }
}
