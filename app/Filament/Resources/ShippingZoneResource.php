<?php

namespace App\Filament\Resources;

use App\Models\ShippingZone;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ShippingZoneResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = ShippingZone::class;

    public static function fields(): array
    {
        return [TextInput::make('name')->required()->maxLength(255), TagsInput::make('districts')->required()->helperText('Exact district names, or * for all districts'),
            TextInput::make('charge')->label('Charge in poisha')->integer()->minValue(0)->maxValue(100000000)->required(), Toggle::make('active')->default(true)];
    }

    public static function columns(): array
    {
        return ['id', 'name', 'charge', 'active'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageShippingZoneRecords::route('/')];
    }
}
