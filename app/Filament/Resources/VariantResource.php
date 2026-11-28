<?php

namespace App\Filament\Resources;

use App\Models\Variant;
use App\Support\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class VariantResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Variant::class;

    public static function fields(): array
    {
        return [
            Section::make('Variant identity')->description('Each product / size / color combination and SKU must be unique.')->schema([
                Select::make('product_id')->label('Product')->relationship('product', 'name')->required()->searchable()->preload(),
                TextInput::make('sku')->label('SKU')->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('size')->required()->maxLength(30),
                TextInput::make('color')->required()->maxLength(50),
            ])->columns(2)->columnSpanFull(),
            Section::make('Pricing & inventory')->description('Use Adjust stock from the variant list to receive or remove units. Reserved units are managed by checkout.')->schema([
                TextInput::make('price')->label('Regular price (BDT)')->helperText('In taka, for example 2190. Decimals are rounded to the nearest taka.')->numeric()->minValue(1)->maxValue(1000000)->required()->formatStateUsing(fn ($state) => Money::toTaka($state))->dehydrateStateUsing(fn ($state) => Money::fromTaka($state)),
                TextInput::make('sale_price')->label('Sale price (BDT)')->helperText('In taka. Leave empty for no discount. Must not be higher than the regular price.')->numeric()->minValue(1)->lte('price')->nullable()->formatStateUsing(fn ($state) => Money::toTaka($state))->dehydrateStateUsing(fn ($state) => Money::fromTaka($state)),
                TextInput::make('low_stock_threshold')->label('Low-stock alert (available units)')->integer()->minValue(0)->default(5)->required(),
                Select::make('media_id')->label('Variant image')->relationship('media', 'alt')->searchable()->preload(),
            ])->columns(2)->columnSpanFull(),
        ];
    }

    public static function columns(): array
    {
        return ['id', 'sku', 'product.name', 'size', 'color', 'price', 'stock', 'reserved'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageVariantRecords::route('/')];
    }
}
