<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ProductReviewResource;
use Filament\Resources\Pages\ManageRecords;

class ManageProductReviewRecords extends ManageRecords
{
    protected static string $resource = ProductReviewResource::class;

    protected function getHeaderActions(): array
    {
        return static::$resource::headerActions();
    }
}
