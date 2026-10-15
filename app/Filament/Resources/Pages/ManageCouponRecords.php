<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCouponRecords extends ManageRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
