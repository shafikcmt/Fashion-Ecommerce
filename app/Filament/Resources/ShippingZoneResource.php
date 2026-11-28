<?php

namespace App\Filament\Resources;

use App\Models\ShippingZone;
use App\Support\Money;
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
            TextInput::make('charge')->label('Delivery charge (BDT)')->helperText('In taka, e.g. 70. Decimals are rounded.')->numeric()->minValue(0)->maxValue(1000000)->required()->formatStateUsing(fn ($state) => Money::toTaka($state))->dehydrateStateUsing(fn ($state) => Money::fromTaka($state)), Toggle::make('active')->default(true)];
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
