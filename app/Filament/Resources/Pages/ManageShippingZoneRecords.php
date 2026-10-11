<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ShippingZoneResource;
use Filament\Resources\Pages\ManageRecords;

class ManageShippingZoneRecords extends ManageRecords
{
    protected static string $resource = ShippingZoneResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
