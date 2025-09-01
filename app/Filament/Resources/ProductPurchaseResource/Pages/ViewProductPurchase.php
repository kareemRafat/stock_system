<?php

namespace App\Filament\Resources\ProductPurchaseResource\Pages;

use App\Filament\Resources\ProductPurchaseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists;

class ViewProductPurchase extends ViewRecord
{
    protected static string $resource = ProductPurchaseResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('تفاصيل العملية')
                        ->schema([
                            Infolists\Components\TextEntry::make('product.name')
                                ->label('المنتج')
                                ->weight('semibold')
                                ->color('violet'),
                            Infolists\Components\TextEntry::make('supplier.name')
                                ->label('المورد')
                                ->weight('semibold')
                                ->color('rose'),
                            Infolists\Components\TextEntry::make('quantity')
                                ->label('الكمية'),
                            Infolists\Components\TextEntry::make('product.unit')
                                ->label('الوحدة'),
                        ])->columns(2),

                    Infolists\Components\Section::make('التكاليف')
                        ->schema([
                            Infolists\Components\TextEntry::make('purchase_price')->label('سعر الوحدة')->money('EGP'),
                            Infolists\Components\TextEntry::make('total_cost')->label('التكلفة الإجمالية')->money('EGP'),
                            Infolists\Components\TextEntry::make('product.average_cost')->label('متوسط التكلفة الحالي')->money('EGP'),
                        ])->columns(3),

                    Infolists\Components\Section::make('معلومات إضافية')
                        ->schema([
                            Infolists\Components\TextEntry::make('purchase_date')->label('تاريخ الشراء')->date('d/m/Y'),
                            Infolists\Components\TextEntry::make('supplier_invoice_number')->label('رقم فاتورة المورد'),
                        ])->columns(2),
                ])->columnSpanFull(),
            ])->columns(3);
    }
}
