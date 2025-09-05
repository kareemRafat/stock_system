<?php

namespace App\Filament\Resources\ReturnInvoiceResource\Pages;

use App\Filament\Resources\ReturnInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnInvoice extends ViewRecord
{
    protected static string $resource = ReturnInvoiceResource::class;

    protected static ?string $title = 'عرض فاتورة المرتجع';

    protected static string $view = 'filament.pages.ReturnInvoices.view-return-invoice';
}
