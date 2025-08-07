<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
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
                ->url(function () {
                    $referrer = request()->header('referer');

                    return $referrer ?? \App\Filament\Resources\InvoiceResource::getUrl('index');
                }),
            Actions\DeleteAction::make()
                ->label('حذف الفاتورة')
                ->color('danger')
                ->requiresConfirmation()
                ->successNotificationTitle('تم حذف الفاتورة بنجاح')
                ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl(); // Force redirect to index
    }
}
