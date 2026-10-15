<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ContentBlockResource;
use Filament\Resources\Pages\ManageRecords;

class ManageContentBlockRecords extends ManageRecords
{
    protected static string $resource = ContentBlockResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
