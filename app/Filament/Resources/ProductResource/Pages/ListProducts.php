<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Actions\ProductActions\AddProductsAction;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->slideOver(),
            AddProductsAction::make('addProducts'),
        ];
    }
}
