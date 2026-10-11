<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends CommerceModel
{
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
