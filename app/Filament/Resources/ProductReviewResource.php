<?php

namespace App\Filament\Resources;

use App\Models\ProductReview;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ProductReviewResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = ProductReview::class;

    public static function fields(): array
    {
        return [
            Select::make('status')->options(['pending' => 'Pending (hidden)', 'approved' => 'Approved (public)', 'rejected' => 'Rejected (hidden)'])->required()->helperText('Only approved reviews appear in the store and in product ratings.'),
            TextInput::make('rating')->integer()->minValue(1)->maxValue(5)->required(),
            TextInput::make('name')->label('Reviewer name')->required()->maxLength(100),
            Textarea::make('body')->label('Review')->required()->rows(5)->maxLength(2000)->columnSpanFull(),
        ];
    }

    public static function columns(): array
    {
        return ['id', 'product.name', 'name', 'rating', 'status', 'body'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProductReviewRecords::route('/')];
    }
}
