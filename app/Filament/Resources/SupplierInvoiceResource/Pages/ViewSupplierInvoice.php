<?php

namespace App\Filament\Resources\SupplierInvoiceResource\Pages;

use App\Filament\Resources\SupplierInvoiceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class ViewSupplierInvoice extends ViewRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected static ?string $title = 'عرض فاتورة المورد';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('تفاصيل الفاتورة')
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label('رقم الفاتورة')
                            ->weight('semibold'),
                        TextEntry::make('supplier.name')
                            ->label('اسم المورد')
                            ->color('rose')
                            ->weight('semibold'),
                        TextEntry::make('total_amount')
                            ->label('إجمالي الفاتورة')
                            ->suffix(' جنيه')
                            ->weight('semibold'),
                        TextEntry::make('invoice_date')
                            ->label('تاريخ الفاتورة')
                            ->date('d/m/Y')
                            ->color('purple')
                            ->weight('semibold'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('الأصناف')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('تفاصيل الأصناف')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('اسم المنتج')
                                    ->color('indigo')
                                    ->weight('semibold'),
                                TextEntry::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->weight('semibold'),
                                TextEntry::make('price')
                                    ->label('سعر الوحدة')
                                    ->suffix(' جنيه')
                                    ->numeric()
                                    ->weight('semibold'),
                                TextEntry::make('subtotal')
                                    ->label('الإجمالي')
                                    ->suffix(' جنيه')
                                    ->numeric()
                                    ->weight('semibold'),
                            ])
                            ->columns(4)
                            ->grid(1),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * Add header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('إضافة فاتورة جديدة')
                ->icon('heroicon-o-plus-circle')
                ->url(route('filament.admin.resources.supplier-invoices.create')), // adjust panel if needed
            \Filament\Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(SupplierInvoiceResource::getUrl('index')),
        ];
    }
}
