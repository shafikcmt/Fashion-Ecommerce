<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class CommerceModel extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function localized(string $field): ?string
    {
        return app()->getLocale() === 'bn' ? ($this->getAttribute($field.'_bn') ?: $this->getAttribute($field)) : $this->getAttribute($field);
    }
}
