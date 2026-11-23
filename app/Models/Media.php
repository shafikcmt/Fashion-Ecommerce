<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends CommerceModel
{
    protected $table = 'media';

    protected function casts(): array
    {
        return ['variants' => 'array'];
    }

    /** `srcset` value listing the generated WebP sizes plus the original, or null when none exist. */
    public function srcset(): ?string
    {
        $files = $this->variants['files'] ?? [];
        if ($files === []) {
            return null;
        }
        $sources = [];
        foreach ($files as $width => $path) {
            $sources[] = Storage::disk($this->disk)->url($path).' '.$width.'w';
        }
        $sources[] = $this->url().' '.(int) ($this->variants['width'] ?? 0).'w';

        return implode(', ', $sources);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'media_product');
    }
}
