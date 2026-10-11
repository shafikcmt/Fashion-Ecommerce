<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUserRecords extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
