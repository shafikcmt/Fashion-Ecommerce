<?php

namespace App\Filament\Resources;

use App\Models\ContentBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ContentBlockResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = ContentBlock::class;

    public static function fields(): array
    {
        return [Select::make('type')->options(['hero' => 'Hero slide', 'promotion' => 'Homepage promotion', 'navigation' => 'Navigation link', 'footer' => 'Footer link'])->required(),
            TextInput::make('title')->label('Title (English)')->required()->maxLength(255), TextInput::make('title_bn')->label('Title (Bangla)')->maxLength(255),
            Textarea::make('body')->label('Body (English)')->maxLength(5000), Textarea::make('body_bn')->label('Body (Bangla)')->maxLength(5000),
            TextInput::make('url')->maxLength(255)->helperText('A local path beginning with / or an HTTPS URL'),
            TextInput::make('link_label')->label('Button label (English)')->maxLength(255),
            TextInput::make('link_label_bn')->label('Button label (Bangla)')->maxLength(255), Select::make('media_id')->label('Image')->relationship('media', 'alt')->searchable()->preload(),
            TextInput::make('position')->label('Display order')->integer()->minValue(0)->default(0)->required(), Toggle::make('active')->label('Visible on storefront')->default(true)];
    }

    public static function columns(): array
    {
        return ['id', 'type', 'title', 'position', 'active'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContentBlockRecords::route('/')];
    }
}
