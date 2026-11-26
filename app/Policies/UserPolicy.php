<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function toggleActive(User $user, User $target): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) && $user->id !== $target->id;
    }
}
