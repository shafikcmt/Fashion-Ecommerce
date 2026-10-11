<?php

namespace App\Models;

class Setting extends CommerceModel
{
    public static function value(string $key, string $fallback = ''): string
    {
        return (string) (static::where('key', $key)->value('value') ?? $fallback);
    }
}
