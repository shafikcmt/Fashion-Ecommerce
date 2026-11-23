<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends CommerceModel
{
    protected function casts(): array
    {
        return ['featured' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_product');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_product')->withPivot('position')->orderByPivot('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('status', 'approved');
    }

    /** Adds `approved_reviews_avg_rating` and `approved_reviews_count` to the query. */
    public function scopeWithRating(Builder $query): Builder
    {
        return $query->withAvg('approvedReviews', 'rating')->withCount('approvedReviews');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereHas('variants');
    }

    public function localized(string $field): ?string
    {
        return app()->getLocale() === 'bn' ? ($this->{$field.'_bn'} ?: $this->{$field}) : $this->{$field};
    }

    public function imageSrcset(): ?string
    {
        return $this->media->first()?->srcset();
    }

    public function image(): string
    {
        return $this->media->first()?->url() ?? asset('images/placeholder.svg');
    }
}
