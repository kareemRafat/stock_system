<?php

namespace App\Filament\Resources\ReturnInvoiceResource\Pages;

use App\Filament\Resources\ReturnInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReturnInvoice extends EditRecord
{
    protected static string $resource = ReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
