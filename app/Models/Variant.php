<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Variant extends CommerceModel
{
    protected $guarded = ['id', 'stock', 'reserved'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'sale_price' => 'integer', 'stock' => 'integer', 'reserved' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $variant) {
            if ($variant->price < 1 || ($variant->sale_price !== null && ($variant->sale_price < 1 || $variant->sale_price > $variant->price)) || $variant->stock < 0 || $variant->reserved < 0 || $variant->reserved > $variant->stock) {
                throw ValidationException::withMessages(['price' => 'Prices and inventory must be valid.']);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function price(): int
    {
        return $this->sale_price ?? $this->price;
    }

    public function available(): int
    {
        return $this->stock - $this->reserved;
    }
}
