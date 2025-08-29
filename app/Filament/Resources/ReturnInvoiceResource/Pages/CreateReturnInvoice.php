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
                // Prefill the form with original invoice data
                $itemsData = $originalInvoice->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity_returned' => $item->quantity,
                    ];
                })->toArray();

                $this->form->fill([
                    'customer_id' => $originalInvoice->customer_id,
                    'original_invoice_number' => $originalInvoice->invoice_number,
                    'original_invoice_id' => $originalInvoice->id,
                    'return_invoice_number' => $returnNumber,
                    'notes' => $originalInvoice->notes,
                    'items' => $itemsData,
                ]);
            } else {
                // $this->notify('error', 'Original invoice not found.');
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
            // Create the return invoice (returnInvoice)
            $returnInvoice = static::getModel()::create($data);

            // Create the items (retrunInvoiceItem)
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $price = $product->price ?? 0;
                $subtotal = $item['quantity_returned'] * $price;

                $returnInvoice->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            return $returnInvoice;
        });

        return $returnInvoice;
    }



    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
