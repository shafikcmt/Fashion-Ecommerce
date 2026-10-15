<?php

namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class ProductResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Product::class;

    public static function fields(): array
    {
        return [
            Section::make('Product identity')->description('Create the product first, then add its sizes, colors and stock in Variants.')->schema([
                TextInput::make('name')->label('Name (English)')->required()->maxLength(255),
                TextInput::make('name_bn')->label('Name (Bangla)')->maxLength(255),
                TextInput::make('slug')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->maxLength(255),
                Select::make('category_id')->label('Category')->relationship('category', 'name')->required()->searchable()->preload(),
                Select::make('brand_id')->label('Brand')->relationship('brand', 'name')->searchable()->preload(),
                Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->default('draft')->required(),
                Toggle::make('featured')->label('Show in homepage favourites')->default(false),
            ])->columns(2)->columnSpanFull(),
            Section::make('Descriptions & fit')->description('Bangla fields fall back to English when empty. Body content is plain text.')->schema([
                Textarea::make('description')->label('Description (English)')->rows(4)->required()->maxLength(10000),
                Textarea::make('description_bn')->label('Description (Bangla)')->rows(4)->maxLength(10000),
                Textarea::make('care')->label('Care (English)')->rows(3)->maxLength(5000),
                Textarea::make('care_bn')->label('Care (Bangla)')->rows(3)->maxLength(5000),
                Textarea::make('size_guide')->label('Size guide (English)')->rows(3)->maxLength(5000),
                Textarea::make('size_guide_bn')->label('Size guide (Bangla)')->rows(3)->maxLength(5000),
            ])->columns(2)->columnSpanFull(),
            Section::make('Photography & discovery')->schema([
                Select::make('media')->relationship('media', 'alt')->multiple()->searchable()->preload()->label('Gallery images')->helperText('Upload images in Media first.')->columnSpanFull(),
                Select::make('collections')->relationship('collections', 'name')->multiple()->searchable()->preload(),
                Select::make('tags')->relationship('tags', 'name')->multiple()->searchable()->preload(),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->maxLength(320),
            ])->columns(2)->columnSpanFull(),
        ];
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
