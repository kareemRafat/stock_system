<?php

namespace App\Filament\Actions\InvoiceActions;

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
            ->label('تسديد ')
            ->disabled(fn($record) => $record->status === 'paid')
            ->modalSubmitActionLabel('تسديد فاتورة')
            ->modalHeading(
                fn(Model $record) => new HtmlString('تسديد فاتورة العميل: ' . "<span style='color: #3b82f6 !important;font-weight:bold'>{$record->customer->name}</span>")
            )
            ->form([
                Forms\Components\TextInput::make('paid')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->dehydrated()
                    ->helperText('فى حالة تبقى مبلغ سيتم اضافته الى محفظة العميل')
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
            ])
            ->action(function (array $data, Model $record) {
                if ($data['paid'] <= 0 && (empty($data['removeFromWallet']) || !$data['removeFromWallet'])) {
                    Notification::make()
                        ->title('حدث خطأ فى تسديد الفاتورة لعدم ادخال المبلغ المدفوع او عدم اختيار السداد من الرصيد')
                        ->warning()
                        ->send();
                    return; // or return null;
                }

                DB::transaction(function () use ($data, $record) {
                    // التحقق من وجود طريقة دفع
                    if ($data['paid'] <= 0 && (empty($data['removeFromWallet']) || !$data['removeFromWallet'])) {
                        Notification::make()
                            ->title('يجب إدخال مبلغ أو استخدام الرصيد')
                            ->warning()
                            ->send();
                        return;
                    }

                    $walletAmountUsed = 0;

                    // 1 - استخدام رصيد المحفظة إذا طُلب ذلك
                    if (isset($data['removeFromWallet']) && $data['removeFromWallet']) {
                        $availableBalance = $record->customer->balance;
                        $remainingAmount = $record->total_amount - $data['paid'];
                        $walletAmountUsed = min($availableBalance, max(0, $remainingAmount));

                        // خصم من المحفظة إذا كان هناك مبلغ للاستخدام
                        if ($walletAmountUsed > 0) {
                            $record->customer->wallet()->create([
                                'type' => 'debit',
                                'amount' => $walletAmountUsed,
                                'invoice_id' => $record->id,
                                'invoice_number' => $record->invoice_number,
                            ]);
                        }
                    }

                    // 2 - حساب الفرق بناءً على المدفوع فقط (بدون المحفظة)
                    $difference = $data['paid'] - $record->total_amount;

                    if ($difference > 0) {
                        // دفع زائد → إضافة للرصيد
                        $record->customer->wallet()->create([
                            'type' => 'credit',
                            'amount' => $difference,
                            'invoice_id' => $record->id,
                            'invoice_number' => $record->invoice_number,
                        ]);
                    } elseif ($difference < 0 && (!isset($data['removeFromWallet']) || !$data['removeFromWallet'])) {
                        // نقص في الدفع → خصم من الرصيد (فقط عند عدم استخدام المحفظة)
                        $record->customer->wallet()->create([
                            'type' => 'debit',
                            'amount' => abs($difference),
                            'invoice_id' => $record->id,
                            'invoice_number' => $record->invoice_number,
                        ]);
                    }

                    // 3 - تحديث حالة الفاتورة
                    $record->update([
                        'status' => 'paid'
                    ]);

                    Notification::make()
                        ->title('تمت تسديد الفاتورة بنجاح')
                        ->success()
                        ->send();
                });
            })
            ->color('rose')
            ->extraAttributes(['class' => 'font-semibold'])
            ->icon('heroicon-s-clipboard-document-check');
    }
}
