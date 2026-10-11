<?php

namespace App\Filament\Resources;

use App\Models\Variant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class VariantResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Variant::class;

    public static function fields(): array
    {
        return [Select::make('product_id')->relationship('product', 'name')->required()->searchable()->preload(),
            TextInput::make('sku')->required()->unique(ignoreRecord: true)->maxLength(255),
            TextInput::make('size')->required()->maxLength(30), TextInput::make('color')->required()->maxLength(50),
            TextInput::make('price')->label('Price in poisha (100 = BDT 1)')->integer()->minValue(1)->maxValue(100000000)->required(),
            TextInput::make('sale_price')->label('Sale price in poisha')->integer()->minValue(1)->lte('price')->nullable(),
            TextInput::make('low_stock_threshold')->integer()->minValue(0)->default(5)->required(),
            Select::make('media_id')->relationship('media', 'alt')->searchable()->preload()];
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
