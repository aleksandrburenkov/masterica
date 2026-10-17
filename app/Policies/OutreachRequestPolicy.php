<?php

namespace App\Policies;

use App\Models\OutreachRequest;
use App\Models\User;

class OutreachRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, OutreachRequest $request): bool
    {
        return $user->isStaff() || $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, OutreachRequest $request): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, OutreachRequest $request): bool
    {
        return $user->isOwner();
    }
}
