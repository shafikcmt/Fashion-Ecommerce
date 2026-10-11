<?php

namespace App\Filament\Resources;

use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class SettingResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Setting::class;

    public static function fields(): array
    {
        return [Select::make('key')->options(array_combine(['brand_name', 'brand_color', 'brand_story', 'logo_media_id', 'social_media_id', 'contact_email', 'contact_phone', 'contact_address', 'announcement', 'announcement_bn', 'seo_description'], ['Brand name', 'Brand color (#RRGGBB)', 'Brand story', 'Logo media ID', 'Social image media ID', 'Contact email', 'Contact phone', 'Contact address', 'Announcement (English)', 'Announcement (Bangla)', 'SEO description']))->required()->unique(ignoreRecord: true),
            Textarea::make('value')->maxLength(5000)->required()];
    }

    public static function columns(): array
    {
        return ['id', 'key', 'value'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSettingRecords::route('/')];
    }
}
