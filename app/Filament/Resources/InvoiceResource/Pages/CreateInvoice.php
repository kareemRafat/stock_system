<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\Concerns\HasWizard;


class CreateInvoice extends CreateRecord
{
    use HasWizard;

    protected static string $resource = InvoiceResource::class;

    // to remove form submission button and but it in last step
    public function getSteps(): array
    {
        return [
            Step::make('Order')
                ->label('الطلب')
                ->schema(InvoiceResource::getInvoiceInformation()),
            Step::make('order_items')
                ->label('اصناف الفاتورة')
                ->schema(InvoiceResource::getInvoiceItemsInfo()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    // Get the referrer URL
                    $referrer = request()->header('referer');

                    // Check if coming from customers index with queries
                    if ($referrer && str_contains($referrer, route('filament.admin.resources.customers.index'))) {
                        return $referrer; // Preserve full URL with queries
                    }

                    // return to index with same index queries
                    return InvoiceResource::getUrl('index', ['_query' => request()->query()]);
                }),
        ];
    }

    protected function afterCreate(): void
    {

        $this->record->update([
            'total_amount' => $this->record->items()->sum('subtotal'),
        ]);

        DB::transaction(function () {
            $customer = $this->record->customer;

            // 2. Update the total amount after creating the invoice


            // 3. If removeFromWallet is checked, try to deduct from wallet
            /*
            if ($this->data['removeFromWallet']) {
                $walletBalance = $customer->balance;
                $totalAmount = $this->record->total_amount;

                if ($walletBalance > 0) {
                    $amountToDeduct = $totalAmount;

                    if ($walletBalance >= $amountToDeduct) {
                        // Full deduction
                        $customer->wallet()->create([
                            'type' => 'invoice',
                            'amount' => $amountToDeduct,
                            'invoice_id' => $this->record->id,
                            'invoice_number' => $this->record->invoice_number,
                        ]);
                    } else {
                        // Partial deduction
                        $customer->wallet()->create([
                            'type' => 'invoice',
                            'amount' => $walletBalance,
                            'invoice_id' => $this->record->id,
                            'invoice_number' => $this->record->invoice_number,
                        ]);

                        // Update invoice total to reflect what's left to be paid
                        $remainingAmount = $totalAmount - $walletBalance;
                        $this->record->update([
                            'total_amount' => $remainingAmount,
                        ]);
                    }
                } else {
                    // Notify: insufficient wallet balance
                    Notification::make()
                        ->title('لا يوجد رصيد كافي في محفظة العميل')
                        ->body('لا يمكن خصم إجمالي الفاتورة من محفظة العميل لأنه لا يوجد رصيد كافي.')
                        ->danger()
                        ->persistent()
                        ->duration(5000)
                        ->send();
                }
            }
            */
        });
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }
}
