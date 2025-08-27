<?php

namespace App\Filament\Resources\SupplierInvoiceResource\Pages;

use App\Filament\Resources\SupplierInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierInvoice extends CreateRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        foreach ($this->record->items as $item) {

            //! rev this
            if ($item->sell_price && $item->product) {
                $item->product->update([
                    'production_price' => $item->sell_price, // update product table
                ]);
            }

            if ($item->product && $item->quantity) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
        }
    }
}
