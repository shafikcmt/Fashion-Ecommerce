<?php

namespace App\Models;

class ShippingZone extends CommerceModel
{
    protected function casts(): array
    {
        return ['districts' => 'array', 'active' => 'boolean', 'charge' => 'integer'];
    }
}
