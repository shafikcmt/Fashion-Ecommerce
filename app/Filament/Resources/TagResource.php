<?php

namespace App\Filament\Resources;

use App\Models\Tag;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class TagResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Tag::class;

    public static function fields(): array
    {
        return [TextInput::make('name')->required()->maxLength(255), TextInput::make('name_bn')->maxLength(255), TextInput::make('slug')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->maxLength(255), Textarea::make('description')->maxLength(5000)];
    }

    public static function columns(): array
    {
        return ['id', 'name', 'slug'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTagRecords::route('/')];
    }
}
