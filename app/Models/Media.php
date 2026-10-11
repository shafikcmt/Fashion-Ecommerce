<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends CommerceModel
{
    protected $table = 'media';

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'media_product');
    }
}
