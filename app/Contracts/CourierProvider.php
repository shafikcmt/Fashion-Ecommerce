<?php

namespace App\Contracts;

use App\Models\Order;

interface CourierProvider
{
    public function dispatch(Order $order): string;

    public function track(string $trackingNumber): array;
}
