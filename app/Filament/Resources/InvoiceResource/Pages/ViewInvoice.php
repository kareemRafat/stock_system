<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Session;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\InvoiceResource;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'عرض الفاتورة';

    protected static string $view = 'filament.pages.invoices.view-invoice';


    public static function getEloquentQuery()
    {
        return parent::getEloquentQuery()->with(['items.product', 'customer']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_return')
                ->label('عمل مرتجع')
                ->color('danger') // Red color
                ->url(function ($record) {
                    return url('/return-invoices/create?original_invoice=' . $record->id);
                })
                ->openUrlInNewTab(false),

            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    return InvoiceResource::getUrl('index');
                }),
        ];
    }
}
