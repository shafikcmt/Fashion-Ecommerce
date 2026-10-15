<?php

namespace App\Models;

use Illuminate\Validation\ValidationException;

class Coupon extends CommerceModel
{
    protected $attributes = ['type' => 'fixed', 'minimum' => 0, 'usage_limit' => 100, 'per_customer_limit' => 1, 'uses' => 0, 'active' => true];

    protected $guarded = ['id', 'uses'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $coupon) {
            $coupon->code = strtoupper(trim($coupon->code));
            if (! in_array($coupon->type, ['fixed', 'percent'], true) || $coupon->value < 1 || ($coupon->type === 'percent' && $coupon->value > 100) || $coupon->usage_limit < 1 || $coupon->per_customer_limit < 1 || $coupon->minimum < 0 || $coupon->usage_limit < $coupon->uses) {
                throw ValidationException::withMessages(['value' => 'Enter a valid coupon value and usage limits.']);
            }
        });
    }
}
