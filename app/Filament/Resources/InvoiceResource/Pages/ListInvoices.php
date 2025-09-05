<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Components\Tab;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('كل الفواتير')
                ->modifyQueryUsing(fn(Builder $query) => $query),

            // 'with_returns' => Tab::make('فواتير بها مرتجع')
            //     ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('returnInvoices')),

            // 'returns_only' => Tab::make('فواتير المرتجع')
            //     ->url(route('filament.admin.resources.return-invoices.index'))
            //     ->icon('heroicon-o-arrow-path'),
        ];
    }
}
