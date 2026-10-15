<?php

namespace App\Filament\Resources;

use App\Models\ContentBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class ContentBlockResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = ContentBlock::class;

    public static function fields(): array
    {
        return [
            Section::make('Placement & visibility')->schema([
                Select::make('type')->label('Content placement')->options(['hero' => 'Hero slide', 'promotion' => 'Homepage promotion', 'navigation' => 'Navigation link', 'footer' => 'Footer link'])->required(),
                TextInput::make('position')->label('Display order')->integer()->minValue(0)->default(0)->required(),
                Toggle::make('active')->label('Visible on storefront')->default(true),
            ])->columns(2)->columnSpanFull(),
            Section::make('English & Bangla content')->description('Bangla fields fall back to English when empty. Body content is plain text.')->schema([
                TextInput::make('title')->label('Title (English)')->required()->maxLength(255),
                TextInput::make('title_bn')->label('Title (Bangla)')->maxLength(255),
                Textarea::make('body')->label('Body (English)')->rows(4)->maxLength(5000),
                Textarea::make('body_bn')->label('Body (Bangla)')->rows(4)->maxLength(5000),
            ])->columns(2)->columnSpanFull(),
            Section::make('Image & destination')->schema([
                Select::make('media_id')->label('Image')->relationship('media', 'alt')->searchable()->preload()->helperText('Upload images in Media first.'),
                TextInput::make('url')->label('Link destination')->maxLength(255)->helperText('A local path beginning with / or an HTTPS URL'),
                TextInput::make('link_label')->label('Button label (English)')->maxLength(255),
                TextInput::make('link_label_bn')->label('Button label (Bangla)')->maxLength(255),
            ])->columns(2)->columnSpanFull(),
        ];
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
