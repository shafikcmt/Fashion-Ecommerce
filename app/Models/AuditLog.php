<?php

namespace App\Models;

class AuditLog extends CommerceModel
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['fields' => 'array', 'created_at' => 'datetime'];
    }
}
