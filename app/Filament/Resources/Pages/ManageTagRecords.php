<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\TagResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTagRecords extends ManageRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
