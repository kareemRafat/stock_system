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

        // Decrease stock for each product in items
        foreach ($this->record->items as $item) {
            if ($item->product) {
                $item->product->decrement('stock_quantity', $item->quantity);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }
}
