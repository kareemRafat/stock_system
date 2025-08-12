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
            ->label('تسديد الفاتورة')
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
                    ->helperText('إذا كان العميل لديه رصيد في المحفظة، سيتم خصم إجمالي الفاتورة من رصيده')
                    ->columnSpan(2)
                    ->inline(false),
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
                DB::transaction(function () use ($data, $record) {
                    // 2 - remove from wallet
                    if ($data['removeFromWallet']) {
                        $walletBalance = $record->customer->balance;
                        $totalAmount   = $record->total_amount;
                        $paidFromForm  = (float) $data['paid'];
                        $totalPaid     = 0; // track combined payments

                        if ($walletBalance > 0) {
                            if ($walletBalance >= $totalAmount) {
                                // Wallet covers entire invoice
                                $record->customer->wallet()->create([
                                    'type'           => 'invoice',
                                    'amount'         => $totalAmount,
                                    'invoice_id'     => $record->id,
                                    'invoice_number' => $record->invoice_number,
                                ]);

                                $totalPaid = $totalAmount;
                            } else {
                                // Wallet covers part → deduct wallet first
                                $record->customer->wallet()->create([
                                    'type'           => 'invoice',
                                    'amount'         => $walletBalance,
                                    'invoice_id'     => $record->id,
                                    'invoice_number' => $record->invoice_number,
                                ]);

                                // The rest comes from form payment
                                $remainingAmount = $totalAmount - $walletBalance;
                                $totalPaid       = $walletBalance + $paidFromForm;

                                // If there's still some unpaid after wallet + paidFromForm,
                                // update invoice total to remaining unpaid
                                // if ($totalPaid < $totalAmount) {
                                //     $record->update([
                                //         'total_amount' => $totalAmount - $totalPaid,
                                //     ]);
                                // }
                            }
                        } else {
                            // No wallet balance → just use form payment
                            $totalPaid = $paidFromForm;

                            // if ($totalPaid < $totalAmount) {
                            //     $this->record->update([
                            //         'total_amount' => $totalAmount - $totalPaid,
                            //     ]);
                            // } elseif ($totalPaid >= $totalAmount) {
                            //     // Paid in full directly
                            //     $this->record->update([
                            //         'total_amount' => 0,
                            //         'status'       => 'paid',
                            //     ]);
                            // }
                        }

                        // If full invoice is covered, mark as paid
                        // if ($totalPaid >= $totalAmount) {
                        //     $record->update(['status' => 'paid']);
                        // }
                    }
                    // 2 - add to wallet if there is big or less payment
                    $difference = $data['paid'] - $record->total_amount;

                    if ($difference > 0) {
                        // Overpayment → add to balance
                        $record->customer->wallet()->create([
                            'type' => 'credit',
                            'amount' => $difference,
                            'invoice_id' => $record->id,
                            'invoice_number' => $record->invoice_number,
                        ]);
                    } elseif ($difference < 0) {
                        // Underpayment → subtract from balance
                        $record->customer->wallet()->create([
                            'type' => 'debit',
                            'amount' => abs($difference),
                            'invoice_id' => $record->id,
                            'invoice_number' => $record->invoice_number,
                        ]);
                    }



                    // 3 - add paid to invoice status
                    Invoice::find($record->id)->update([
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
