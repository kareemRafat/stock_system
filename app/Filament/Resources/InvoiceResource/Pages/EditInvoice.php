<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Wizard\Step;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\Concerns\HasWizard;

class EditInvoice extends EditRecord
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
                ->url(fn() => Session::get('previous_url') ?? InvoiceResource::getUrl('index')),
            Actions\DeleteAction::make()
                ->label('حذف الفاتورة')
                ->color('danger')
                ->requiresConfirmation()
                ->successNotificationTitle('تم حذف الفاتورة بنجاح'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl(); // Force redirect to index
    }

    public function mount($record): void
    {
        parent::mount($record);

        // Save the previous URL to the session
        Session::flash('previous_url', url()->previous());
    }

    /**
     * After save hook for updating stock.
     */
    protected function afterSave(): void
    {
        $this->updateStockQuantities();
    }

    /**
     * Handle stock recalculation when editing invoice
     */
    private function updateStockQuantities(): void
    {
        // Recalculate total
        $this->record->update([
            'total_amount' => $this->record->items()->sum('subtotal'),
        ]);

        // Reset stock from old invoice quantities
        foreach ($this->record->items()->getOriginal() as $item) {
            if ($item->product) {
                $item->product->increment('stock_quantity', $item->getOriginal('quantity') ?? 0);
            }
        }

        // Apply new stock decrements
        foreach ($this->record->items as $item) {
            if ($item->product) {
                $item->product->decrement('stock_quantity', $item->quantity);
            }
        }
    }
}
