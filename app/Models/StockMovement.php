<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends CommerceModel
{
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
