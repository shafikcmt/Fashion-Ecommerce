<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\VariantResource;
use Filament\Resources\Pages\ManageRecords;

class ManageVariantRecords extends ManageRecords
{
    protected static string $resource = VariantResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
