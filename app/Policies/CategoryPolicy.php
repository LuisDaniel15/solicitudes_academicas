<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function toggleActive(User $user, Category $category): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }
}
