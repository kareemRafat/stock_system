<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

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
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    // Get the referrer URL
                    $referrer = request()->header('referer');

                    // Check if coming from customers index with queries
                    if ($referrer && str_contains($referrer, route('filament.admin.resources.customers.index'))) {
                        return $referrer; // Preserve full URL with queries
                    }

                    // return to index with same index queries
                    return InvoiceResource::getUrl('index', ['_query' => request()->query()]);
                }),
        ];
    }
}
