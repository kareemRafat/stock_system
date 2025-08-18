<?php

// Enhanced version: app/Filament/Actions/ViewCustomerDetailsAction.php

namespace App\Filament\Actions\CustomerActions;

use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;

class ViewCustomerDetailsAction
{
    public static function make(): Action
    {
        return Action::make('viewCustomerDetails')
            ->label('معلومات')
            ->modalHeading(fn($record) => $record ? 'معلومات العميل:  ' . $record->name : 'معلومات العميل')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->modalWidth('3xl')
            ->icon('heroicon-o-eye')
            ->color('indigo')
            ->tooltip('اضغط لعرض تفاصيل العميل')
            ->infolist(function ($record) {
                return $record ? self::getInfolistSchema() : [];
            });
    }

    protected static function getInfolistSchema(): array
    {
        return [
            Section::make('المعلومات الأساسية')
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('name')
                                ->label('اسم العميل')
                                ->weight('bold')
                                ->size('md')
                                ->icon('heroicon-o-user')
                                ->color('primary'),

                            TextEntry::make('phone')
                                ->label('رقم الهاتف')
                                ->copyable()
                                ->copyMessage('تم نسخ رقم الهاتف')
                                ->placeholder('لايوجد')
                                ->icon('heroicon-o-phone')
                                ->color('orange')
                                ->weight('bold'),

                            TextEntry::make('phone2')
                                ->label('رقم احتياطي')
                                ->copyable()
                                ->copyMessage('تم نسخ الرقم الاحتياطي')
                                ->placeholder('لايوجد')
                                ->icon('heroicon-o-phone')
                                ->color('warning'),

                            TextEntry::make('balance')
                                ->label('رصيد العميل')
                                ->placeholder('لايوجد')
                                ->icon('heroicon-o-banknotes')
                                ->formatStateUsing(
                                    fn($state) => $state == 0
                                        ? '0 ج.م'
                                        : number_format($state, 2) . ' ج.م'
                                )
                                ->color(
                                    fn($state) =>
                                    $state < 0 ? 'rose' : ($state > 0 ? 'success' : 'gray')
                                )
                                ->weight('bold')
                                ->extraAttributes(['class' => 'cursor-pointer hover:underline']),
                        ]),
                ])
                ->collapsible(),

            Section::make('معلومات العنوان')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('address')
                                ->label('العنوان')
                                ->placeholder('لايوجد')
                                ->columnSpanFull()
                                ->size('md')
                                ->weight('bold')
                                ->color('indigo')
                                ->icon('heroicon-o-map-pin')
                                ->copyable(),

                            TextEntry::make('city')
                                ->label('المدينة')
                                ->weight('semibold')
                                ->placeholder('لايوجد')
                                ->icon('heroicon-o-building-office'),

                            TextEntry::make('governorate')
                                ->label('المحافظة')
                                ->placeholder('لايوجد')
                                ->weight('semibold')
                                ->icon('heroicon-o-globe-alt'),
                        ]),
                ])
                ->collapsible(),
        ];
    }

    // For table actions column
    public static function forTable(): Action
    {
        return self::make()
            ->label('عرض')
            ->tooltip('عرض تفاصيل العميل');
    }

    // For header actions
    public static function forHeader(): Action
    {
        return self::make()
            ->label('عرض التفاصيل')
            ->outlined();
    }
}
