<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class CommercePolicy
{
    public function __construct(private string|array $group, private bool $readOnly = false) {}

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return ! $this->readOnly && $this->allowed($user);
    }

    public function update(User $user, Model $record): bool
    {
        return ! $this->readOnly && $this->allowed($user);
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function restore(User $user, Model $record): bool
    {
        return false;
    }

    private function allowed(User $user): bool
    {
        return $user->hasVerifiedEmail() && ($user->role === 'super_admin' || in_array($user->role, (array) $this->group, true));
    }
}
