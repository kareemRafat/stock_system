<?php

namespace App\Filament\Actions\ProductActions;

use Filament\Forms;
use Filament\Tables\Actions\Action;

class AddStockAction
{
    public static function make(): Action
    {
        return Action::make('addStock')
            ->label('إضافة رصيد')
            ->icon('heroicon-o-plus-circle')
            ->color('teal')
            ->tooltip('إضافة كمية جديدة للمخزن')
            ->modalHeading('إضافة كمية للمخزن')
            ->modalSubmitActionLabel('حفظ')
            ->form([
                Forms\Components\TextInput::make('amount')
                    ->label('الكمية المضافة')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
            ])
            ->action(function (array $data, $record) {
                $record->increment('stock_quantity', $data['amount']);
            })
            ->successNotificationTitle('تمت إضافة الكمية بنجاح');
    }

    /** For using in tables */
    public static function forTable(): Action
    {
        return self::make()
            ->label('إضافة');
    }

    /** For using in header actions (if you want later) */
    public static function forHeader(): Action
    {
        return self::make()
            ->label('إضافة رصيد')
            ->outlined();
    }
}
