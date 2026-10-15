<?php

namespace App\Payments;

use App\Contracts\PaymentProvider;
use App\Models\Order;
use LogicException;

final class DisabledProvider implements PaymentProvider
{
    public function __construct(public readonly string $name) {}

    public function enabled(): bool
    {
        return false;
    }

    public function initiate(Order $order): array
    {
        throw new LogicException($this->name.' requires verified documentation and sandbox credentials.');
    }

    public function verify(string $transactionId): array
    {
        throw new LogicException('Provider is disabled.');
    }

    public function handleWebhook(array $payload, array $headers): void
    {
        throw new LogicException('Provider is disabled.');
    }
}
