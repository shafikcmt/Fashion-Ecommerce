<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\PageResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePageRecords extends ManageRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
