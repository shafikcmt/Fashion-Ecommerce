<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends CommerceModel
{
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
