<?php

namespace App\Filament\Resources;

use App\Models\Page;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class PageResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Page::class;

    public static function fields(): array
    {
        return [TextInput::make('title')->required()->maxLength(255), TextInput::make('title_bn')->maxLength(255),
            TextInput::make('slug')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->maxLength(255),
            Toggle::make('published')->default(false), Textarea::make('body')->required()->maxLength(20000)->helperText('Plain text. HTML is not rendered.')->columnSpanFull(),
            Textarea::make('body_bn')->maxLength(20000)->columnSpanFull(), Textarea::make('seo_description')->maxLength(320)];
    }

    public static function columns(): array
    {
        return ['id', 'title', 'slug', 'published'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePageRecords::route('/')];
    }
}
