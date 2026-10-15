<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCategoryRecords extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
