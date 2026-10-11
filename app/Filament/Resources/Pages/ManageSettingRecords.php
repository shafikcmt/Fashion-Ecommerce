<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSettingRecords extends ManageRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
