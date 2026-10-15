<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\CollectionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCollectionRecords extends ManageRecords
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
