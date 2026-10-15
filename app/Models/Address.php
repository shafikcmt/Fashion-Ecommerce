<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends CommerceModel
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
