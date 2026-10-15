<?php

namespace App\Filament\Resources;

use App\Models\StockMovement;

class StockMovementResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = StockMovement::class;

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return ['id', 'variant.sku', 'quantity', 'reason', 'user_id', 'created_at'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageStockMovementRecords::route('/')];
    }
}
