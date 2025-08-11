<?php

namespace App\Filament\Resources\OutsourcedProductionResource\Pages;

use App\Filament\Resources\OutsourcedProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOutsourcedProductions extends ListRecords
{
    protected static string $resource = OutsourcedProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->createAnother(false),
        ];
    }
}
