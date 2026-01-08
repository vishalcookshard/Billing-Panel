<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $user): bool
    {
        return $actor->id === $user->id || $actor->hasRole('admin') || $actor->is_admin;
    }

    public function update(User $actor, User $user): bool
    {
        // Only self or admin can update
        return $actor->id === $user->id || $actor->hasRole('admin') || $actor->is_admin;
    }

    public function delete(User $actor, User $user): bool
    {
        // Only admin can delete
        return $actor->hasRole('admin') || $actor->is_admin;
    }

    public function assignRole(User $actor, User $user): bool
    {
        // Only admin can assign roles
        return $actor->hasRole('admin') || $actor->is_admin;
    }
}
