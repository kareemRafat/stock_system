<?php

namespace App\Filament\Resources\SupplierInvoiceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\SupplierInvoiceResource;

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(url()->previous() ?? SupplierInvoiceResource::getUrl('index')),
            // if no "previous", fallback to index
        ];
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
