<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageOrderRecords extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
