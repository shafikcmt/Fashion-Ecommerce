<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ReturnRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageReturnRequestRecords extends ManageRecords
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
