<?php

namespace App\Filament\Resources;

use App\Models\ReturnRequest;

class ReturnRequestResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = ReturnRequest::class;

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return ['id', 'order_id', 'reason', 'status', 'restocked_at'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReturnRequestRecords::route('/')];
    }
}
