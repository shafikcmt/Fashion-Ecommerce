<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id || $this->manage($user);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    public function manage(User $user): bool
    {
        return $user->hasVerifiedEmail() && in_array($user->role, ['super_admin', 'order_manager'], true);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->manage($user) || ($order->user_id === $user->id && $order->status === 'pending');
    }
}
