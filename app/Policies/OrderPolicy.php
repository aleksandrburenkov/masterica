<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isStaff() || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isStaff();
    }

    /** Owners may cancel their own not-yet-completed order. */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $order->user_id === $user->id
            && in_array($order->status->value, ['draft', 'pending_payment', 'paid'], true);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isOwner();
    }
}
