<?php

namespace App\Models;

class Page extends CommerceModel
{
    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    public function localized(string $field): ?string
    {
        return app()->getLocale() === 'bn' ? ($this->{$field.'_bn'} ?: $this->{$field}) : $this->{$field};
    }
}
