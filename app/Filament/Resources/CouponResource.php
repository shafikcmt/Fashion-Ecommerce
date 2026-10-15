<?php

namespace App\Filament\Resources;

use App\Models\Category;
use App\Models\Coupon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CouponResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Coupon::class;

    public static function fields(): array
    {
        return [TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(50),
            Select::make('type')->options(['fixed' => 'Fixed amount in poisha', 'percent' => 'Percentage'])->default('fixed')->required(),
            TextInput::make('value')->integer()->minValue(1)->maxValue(100000000)->required(),
            TextInput::make('minimum')->label('Minimum bag subtotal in poisha')->integer()->minValue(0)->default(0)->required(),
            Select::make('category_id')->options(Category::pluck('name', 'id'))->searchable()->label('Eligible category (empty = all)'),
            TextInput::make('usage_limit')->integer()->minValue(1)->default(100)->required(), TextInput::make('per_customer_limit')->integer()->minValue(1)->default(1)->required(),
            DateTimePicker::make('expires_at')->required(), Toggle::make('active')->default(true)];
    }

    public static function columns(): array
    {
        return ['id', 'code', 'type', 'value', 'uses', 'usage_limit', 'expires_at'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCouponRecords::route('/')];
    }
}
