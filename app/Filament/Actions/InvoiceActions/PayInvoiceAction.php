<?php

namespace App\Filament\Actions\InvoiceActions;

use App\Filament\Forms\Components\ClientDateTimeFormComponent;
use Filament\Forms;
use App\Models\CustomerWallet;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class PayInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('payInvoice')
            ->label('سداد')
            ->disabled(fn($record) => $record->status === 'paid')
            ->modalSubmitActionLabel('تسديد فاتورة')
            ->modalHeading(
                fn(Model $record) => new HtmlString('تسديد فاتورة العميل: ' . "<span style='color: #3b82f6 !important'>{$record->customer->name}</span>")
            )
            ->form([
                Forms\Components\TextInput::make('paid')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->dehydrated()
                    ->helperText('فى حالة تبقى مبلغ سيتم اضافته الى محفظة العميل - وفي حالة وجود مديونية للعميل سيتم سحب المديونية من الرصيد')
                    ->columnSpan(1),
                Forms\Components\Toggle::make('removeFromWallet')
                    ->label('خصم من محفظة العميل')
                    ->default(false)
                    ->dehydrated(fn($state) => $state !== null)
                    ->helperText('خصم من رصيد العميل وفي حالة وجود باقي يتم اضافته الى المديونية ')
                    ->columnSpan(2)
                    ->inline(false)
                    ->disabled(fn($record) => $record->customer->balance <= 0),
                Forms\Components\Placeholder::make('wallet_balance')
                    ->label('رصيد المحفظة الحالي')
                    ->content(function ($record) {
                        if (! $record?->customer) {
                            return '— لا يوجد عميل —';
                        }

                        $balance = $record->customer->balance ?? 0;
                        return number_format($balance, 2) . ' ج.م';
                    })
                    ->extraAttributes(function ($record) {
                        $balance = $record?->customer?->balance ?? 0;
                        $color = $balance > 0 ? '#16a34a' : ($balance < 0 ? '#dc2626' : '#1f2937'); // green/red/gray
                        return ['style' => "color: {$color}; font-weight: 700;"];
                    }),
                    // get js date
                    ClientDateTimeFormComponent::make('created_at'),
            ])
            ->action(function (array $data, Model $record) {
                if ($data['paid'] <= 0 && (empty($data['removeFromWallet']) || !$data['removeFromWallet'])) {
                    return self::notifyError('حدث خطأ: لم يتم إدخال مبلغ أو اختيار السداد من الرصيد');
                }

                DB::transaction(function () use ($data, $record) {
                    $customer = $record->customer;
                    $paid = $data['paid'] ?? 0;
                    $total = $record->total_amount;

                    $walletUsed = self::useWalletIfRequested($customer, $record, $paid, $total, $data);
                    $remaining = self::calculateRemaining($paid, $total, $walletUsed);

                    self::handleRemaining($customer, $record, $remaining);
                    self::updateInvoiceStatus($record, $remaining);

                    self::notifySuccess('تمت عملية التسديد بنجاح');
                });
            })
            ->color('rose')
            // ->extraAttributes(['class' => 'font-semibold'])
            ->icon('heroicon-s-clipboard-document-check');
    }

    protected static function useWalletIfRequested($customer, $record, $paid, $total, $data): float
    {
        $walletAmountUsed = 0;

        if (!empty($data['removeFromWallet']) && $data['removeFromWallet']) {
            $remaining = $total - $paid;
            $availableBalance = $customer->balance;

            if ($remaining > 0) {
                $walletAmountUsed = min($availableBalance, $remaining);

                if ($walletAmountUsed > 0) {
                    $customer->wallet()->create([
                        'type' => 'debit',
                        'amount' => $walletAmountUsed,
                        'invoice_id' => $record->id,
                        'invoice_number' => $record->invoice_number,
                        'notes' => 'خصم من المحفظة لسداد الفاتورة',
                    ]);
                }
            }
        }

        return $walletAmountUsed;
    }

    protected static function calculateRemaining($paid, $total, $walletUsed): float
    {
        return $total - ($paid + $walletUsed);
    }

    protected static function handleRemaining($customer, $record, float $remaining): void
    {
        if ($remaining > 0) {
            $customer->wallet()->create([
                'type' => 'debit',
                'amount' => $remaining,
                'invoice_id' => $record->id,
                'invoice_number' => $record->invoice_number,
                'notes' => 'مديونية متبقية من الفاتورة',
            ]);
        } elseif ($remaining < 0) {
            $customer->wallet()->create([
                'type' => 'credit',
                'amount' => abs($remaining),
                'invoice_id' => $record->id,
                'invoice_number' => $record->invoice_number,
                'notes' => 'رصيد زائد من الفاتورة',
            ]);
        }
    }

    protected static function updateInvoiceStatus($record, float $remaining): void
    {

        $record->update(['status' => 'paid']);

        /*
        if ($remaining == 0) {
            $record->update(['status' => 'paid']);
        } elseif ($remaining > 0) {
            $record->update(['status' => 'pending']);
        } else {
            $record->update(['status' => 'paid']);
        }
        */
    }

    protected static function notifyError(string $message)
    {
        Notification::make()
            ->title($message)
            ->warning()
            ->send();
    }

    protected static function notifySuccess(string $message)
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
}
