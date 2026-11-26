<?php

namespace App\Policies;

use App\Models\SupportRequest;
use App\Models\User;

class SupportRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupportRequest $request): bool
    {
        if ($user->hasRole(User::ROLE_STUDENT)) {
            return $request->user_id === $user->id;
        }

        if ($user->hasRole(User::ROLE_OFFICER)) {
            return $request->assigned_to === $user->id;
        }

        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_SUPERVISOR);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_STUDENT);
    }

    public function assign(User $user, SupportRequest $request): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_SUPERVISOR);
    }

    public function changeStatus(User $user, SupportRequest $request): bool
    {
        return $user->hasRole(User::ROLE_SUPERVISOR)
            || ($user->hasRole(User::ROLE_OFFICER) && $request->assigned_to === $user->id);
    }

    public function addObservation(User $user, SupportRequest $request): bool
    {
        return $user->hasRole(User::ROLE_OFFICER) && $request->assigned_to === $user->id;
    }
}
