<?php

namespace App\Filament\Actions\CustomerActions;

use Filament\Forms;
use App\Models\CustomerWallet;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class AdjustBalanceAction
{
    public static function make(): Action
    {
        return Action::make('adjustBalance')
            ->label('إضافة رصيد')
            ->modalSubmitActionLabel('إضافة رصيد')
            ->modalHeading(
                fn(Model $record) => new HtmlString('إضافة رصيد العميل: ' . "<span style='color: #3b82f6 !important;font-weight:bold'>{$record->name}</span>")
            )
            ->form([
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required()
                    ->rules(['required', 'numeric', 'min:0.01'])
            ])
            ->action(function (array $data, Model $record) {
                // Create the wallet transaction
                CustomerWallet::create([
                    'customer_id' => $record->id,
                    'type' => 'adjustment',
                    'amount' => $data['amount'],
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ]);

                Notification::make()
                    ->title('تمت إضافة الرصيد بنجاح')
                    ->success()
                    ->send();
            })
            ->color('warning')
            ->extraAttributes(['class' => 'font-semibold'])
            ->icon('heroicon-s-clipboard-document-check');
    }
}
