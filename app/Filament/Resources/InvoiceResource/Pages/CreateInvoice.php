<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\InvoiceResource;
use Filament\Notifications\Notification;
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
        // Update the total amount after creating the invoice
        $this->record->update([
            'total_amount' => $this->record->items()->sum('subtotal'),
        ]);

        // If removeFromWallet is true, deduct from customer's wallet
        if ($this->data['removeFromWallet']) {
            $customer = $this->record->customer;
            $walletBalance = $customer->balance;
            $totalAmount = $this->record->total_amount;

            if ($customer->balance > 0) {
                $amountToDeduct = $this->record->total_amount;

                if ($customer->balance >= $amountToDeduct) {
                    // Deduct from wallet
                    $customer->wallet()->create([
                        'type' => 'invoice',
                        'amount' => $amountToDeduct,
                        'invoice_id' => $this->record->id,
                        'invoice_number' => $this->record->invoice_number,
                    ]);
                } else {
                    // Deduct only the available wallet balance and update invoice total
                    $customer->wallet()->create([
                        'type' => 'invoice',
                        'amount' => $walletBalance,
                        'invoice_id' => $this->record->id,
                        'invoice_number' => $this->record->invoice_number,
                    ]);

                    // Update invoice total_amount to the remaining amount
                    $remainingAmount = $totalAmount - $walletBalance;
                    $this->record->update([
                        'total_amount' => $remainingAmount,
                    ]);
                }
            } else {
                Notification::make()
                    ->title('لا يوجد رصيد كافي في محفظة العميل')
                    ->body('لا يمكن خصم إجمالي الفاتورة من محفظة العميل لأنه لا يوجد رصيد كافي.')
                    ->danger()
                    ->persistent()
                    ->duration(5000)
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl(); // Force redirect to index
    }
}
