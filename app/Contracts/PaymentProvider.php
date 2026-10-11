<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentProvider
{
    public function enabled(): bool;

    public function initiate(Order $order): array;

    public function verify(string $transactionId): array;

    public function handleWebhook(array $payload, array $headers): void;
}
