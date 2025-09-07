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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_return')
                ->label('عمل مرتجع')
                ->color('rose')
                ->icon('heroicon-s-arrow-path')
                ->url(function ($record) {
                    return url('/return-invoices/create?original_invoice=' . $record->id);
                })
                ->openUrlInNewTab(false)
                ->disabled(fn($record) => !$record->hasReturnableItems())
                ->tooltip(
                    fn($record) =>
                    $record->hasReturnableItems()
                        ? 'إنشاء مرتجع للفاتورة'
                        : 'لا توجد منتجات متاحة للاسترجاع'
                ),

            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    return InvoiceResource::getUrl('index');
                }),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Eager load the relationships
        $this->record->load(['items.product']);
    }
}
