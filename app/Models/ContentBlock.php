<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ContentBlock extends CommerceModel
{
    protected static function booted(): void
    {
        static::saving(function (self $block) {
            if ($block->url && ! preg_match('~^(?:/(?!/)|https://)~', $block->url)) {
                throw ValidationException::withMessages(['url' => 'Use a local path or HTTPS URL.']);
            }
        });
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function localized(string $field): ?string
    {
        return app()->getLocale() === 'bn' ? ($this->{$field.'_bn'} ?: $this->{$field}) : $this->{$field};
    }
}
