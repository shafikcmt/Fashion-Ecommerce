<?php

namespace App\Support;

use App\Models\Setting;

/** Optional Facebook Pixel and Google Analytics 4 IDs, read from Settings and validated before use in markup. */
final class Analytics
{
    public static function facebookPixelId(): ?string
    {
        $id = trim(Setting::value('facebook_pixel_id'));

        return preg_match('/^\d{10,20}$/', $id) ? $id : null;
    }

    public static function gaId(): ?string
    {
        $id = trim(Setting::value('ga_measurement_id'));

        return preg_match('/^G-[A-Z0-9]{6,12}$/', $id) ? $id : null;
    }
}
