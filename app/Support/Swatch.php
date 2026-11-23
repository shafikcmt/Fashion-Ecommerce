<?php

namespace App\Support;

final class Swatch
{
    private const COLORS = [
        'red' => '#b3262f', 'লাল' => '#b3262f', 'maroon' => '#7a1f2b', 'মেরুন' => '#7a1f2b',
        'green' => '#2f7a45', 'সবুজ' => '#2f7a45', 'teal' => '#0f6f6b', 'টিল' => '#0f6f6b',
        'blue' => '#2d6fb3', 'নীল' => '#2d6fb3', 'navy' => '#1f2f56', 'নেভি' => '#1f2f56', 'sky blue' => '#7ab8e3', 'আকাশি' => '#7ab8e3',
        'white' => '#f6f1e7', 'সাদা' => '#f6f1e7', 'off white' => '#efe6d4', 'অফ হোয়াইট' => '#efe6d4',
        'black' => '#262626', 'কালো' => '#262626', 'yellow' => '#e9b94a', 'হলুদ' => '#e9b94a',
        'pink' => '#d9577f', 'গোলাপি' => '#d9577f', 'purple' => '#5a2a82', 'বেগুনি' => '#5a2a82',
        'brown' => '#8a4b2a', 'বাদামি' => '#8a4b2a', 'orange' => '#e07b2a', 'কমলা' => '#e07b2a',
        'wine' => '#6b2438', 'ওয়াইন' => '#6b2438', 'rust' => '#c85b3c', 'মরচে' => '#c85b3c', 'multicolor' => '#c9a24b', 'রঙিন' => '#c9a24b',
    ];

    public static function hex(?string $name): ?string
    {
        return self::COLORS[mb_strtolower(trim((string) $name))] ?? null;
    }
}
