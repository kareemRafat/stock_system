<?php

namespace App\Filament\Resources\OutsourcedProductionResource\Pages;

use App\Filament\Resources\OutsourcedProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOutsourcedProduction extends EditRecord
{
    protected static string $resource = OutsourcedProductionResource::class;

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
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
