<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStockMovementRecords extends ManageRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
