<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class OrderItem extends CommerceModel
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Order item snapshots cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Order item snapshots cannot be deleted.'));
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
