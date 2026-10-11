<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ManageRecords;

class ManageProductRecords extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
