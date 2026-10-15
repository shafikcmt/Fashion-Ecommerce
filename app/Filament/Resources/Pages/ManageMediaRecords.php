<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Resources\Pages\ManageRecords;

class ManageMediaRecords extends ManageRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
