<?php

namespace App\Filament\Resources;

use App\Models\Media;
use Filament\Forms\Components\TextInput;

class MediaResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Media::class;

    public static function fields(): array
    {
        return [TextInput::make('alt')->required()->maxLength(255)];
    }

    public static function columns(): array
    {
        return ['id', 'alt', 'path', 'mime', 'bytes'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageMediaRecords::route('/')];
    }
}
