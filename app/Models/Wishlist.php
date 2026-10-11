<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends CommerceModel
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
