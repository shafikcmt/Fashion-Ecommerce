<?php

namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ProductResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Product::class;

    public static function fields(): array
    {
        return [TextInput::make('name')->required()->maxLength(255), TextInput::make('name_bn')->maxLength(255),
            TextInput::make('slug')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->maxLength(255),
            Select::make('category_id')->relationship('category', 'name')->required()->searchable()->preload(),
            Select::make('brand_id')->relationship('brand', 'name')->searchable()->preload(),
            Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->default('draft')->required(),
            Toggle::make('featured')->default(false),
            Textarea::make('description')->required()->maxLength(10000)->columnSpanFull(), Textarea::make('description_bn')->maxLength(10000)->columnSpanFull(),
            Textarea::make('care')->maxLength(5000), Textarea::make('care_bn')->maxLength(5000), Textarea::make('size_guide')->maxLength(5000), Textarea::make('size_guide_bn')->maxLength(5000),
            Select::make('media')->relationship('media', 'alt')->multiple()->searchable()->preload()->label('Gallery (upload images in Media first)'),
            Select::make('collections')->relationship('collections', 'name')->multiple()->searchable()->preload(),
            Select::make('tags')->relationship('tags', 'name')->multiple()->searchable()->preload(),
            TextInput::make('seo_title')->maxLength(255), Textarea::make('seo_description')->maxLength(320)];
    }

    public static function columns(): array
    {
        return ['id', 'name', 'category.name', 'status'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProductRecords::route('/')];
    }
}
