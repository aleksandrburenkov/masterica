<?php

namespace App\Policies;

use App\Models\NotificationRecipient;
use App\Models\User;

/**
 * Управлять списком рассылки может только владелец.
 */
class NotificationRecipientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, NotificationRecipient $model): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, NotificationRecipient $model): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, NotificationRecipient $model): bool
    {
        return $user->isOwner();
    }
}
