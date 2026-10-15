<?php

namespace App\Filament\Resources;

use App\Models\Order;

class OrderResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Order::class;

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return ['id', 'customer_name', 'status', 'fulfillment_status', 'payment_status', 'total', 'created_at'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageOrderRecords::route('/')];
    }
}
