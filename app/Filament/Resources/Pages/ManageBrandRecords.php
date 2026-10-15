<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBrandRecords extends ManageRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
