<?php

namespace App\Filament\Resources\ReturnInvoiceResource\Pages;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
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

        // Calculate total amount
        $totalAmount = 0;
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $price = $product->price ?? 0; // Assuming you have price in product model
            $subtotal = $item['quantity_returned'] * $price;
            $totalAmount += $subtotal;
        }


        // Add total amount to data
        $data['total_amount'] = $totalAmount;

        return DB::transaction(function () use ($data, $items) {
            // Create the return invoice
            $returnInvoice = static::getModel()::create($data);

            foreach ($items as $item) {
                // return all if the checbox is checked
                if (!empty($item['return_all']) && $item['return_all']) {
                    $item['quantity_returned'] = $item['quantity'];
                }

                //تجاهل الصنف لو المرتجع = 0 أو أقل
                if (empty($item['quantity_returned']) || $item['quantity_returned'] <= 0) {
                    continue;
                }

                $product = Product::find($item['product_id']);
                $price = $product->price ?? 0;
                $subtotal = $item['quantity_returned'] * $price;

                $returnInvoice->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);

                // تعديل المخزون: إضافة الكمية المرتجعة مرة أخرى
                if ($product) {
                    $product->increment('stock_quantity', $item['quantity_returned']);
                }
            }

            return $returnInvoice;
        });

        return $returnInvoice;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
