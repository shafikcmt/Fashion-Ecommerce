<?php

namespace App\Support;

final class Money
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public static function format(int $amount): string
    {
        $text = '৳'.preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', (string) intdiv($amount, 100)).($amount % 100 ? '.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT) : '');

        return app()->getLocale() === 'bn' ? self::digits($text) : $text;
    }

    /** Convert ASCII digits to Bangla digits. */
    public static function digits(string|int $value): string
    {
        return strtr((string) $value, array_combine(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], self::BANGLA_DIGITS));
    }

    /** Whole-taka discount percentage between two paisa amounts. */
    public static function percentOff(int $price, int $sale): int
    {
        return $price > 0 && $sale < $price ? (int) round(($price - $sale) / $price * 100) : 0;
    }
}
