<?php

namespace App\Support;

final class Money
{
    public static function format(int $amount): string
    {
        return '৳'.preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', (string) intdiv($amount, 100)).($amount % 100 ? '.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT) : '');
    }
}
