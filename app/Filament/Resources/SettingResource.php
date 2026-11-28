<?php

namespace App\Filament\Resources;

use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class SettingResource extends CommerceResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Setting::class;

    /** @var array<string, string> Setting keys the storefront reads, with their admin labels. */
    public const KEYS = [
        'brand_name' => 'Brand name (English)',
        'brand_name_bn' => 'Brand name (Bangla)',
        'brand_color' => 'Brand color (#RRGGBB)',
        'brand_story' => 'Brand story (English)',
        'brand_story_bn' => 'Brand story (Bangla)',
        'logo_media_id' => 'Logo media ID',
        'social_media_id' => 'Social image media ID',
        'contact_email' => 'Contact email',
        'contact_phone' => 'Contact phone / hotline',
        'contact_address' => 'Contact address',
        'whatsapp' => 'WhatsApp number (digits with country code, e.g. 8801XXXXXXXXX)',
        'facebook_url' => 'Facebook page URL (https://...)',
        'announcement' => 'Announcement (English)',
        'announcement_bn' => 'Announcement (Bangla)',
        'free_delivery_min' => 'Free delivery from (BDT, 0 = off)',
        'bkash_number' => 'bKash number for send-money payments',
        'nagad_number' => 'Nagad number for send-money payments',
        'facebook_pixel_id' => 'Facebook Pixel ID (digits)',
        'ga_measurement_id' => 'Google Analytics ID (G-XXXXXXXX)',
        'seo_description' => 'SEO description',
    ];

    public static function fields(): array
    {
        return [Select::make('key')->label('Storefront setting')->searchable()->options(static::KEYS)->required()->unique(ignoreRecord: true),
            Textarea::make('value')->label('Setting value')->helperText('For logo and social images, use the ID from Media. Brand color uses #RRGGBB.')->rows(4)->columnSpanFull()->maxLength(5000)->required()];
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
