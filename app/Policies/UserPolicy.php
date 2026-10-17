<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageSettings();
    }

    public function view(User $user, User $model): bool
    {
        return $user->role->canManageSettings() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->role->canManageSettings();
    }

    public function update(User $user, User $model): bool
    {
        return $user->role->canManageSettings() || $user->id === $model->id;
    }

    /** Only the owner may change roles or delete accounts. */
    public function updateRole(User $user, User $model): bool
    {
        return $user->role->canManageSettings();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role->canManageSettings() && $user->id !== $model->id;
    }
}
