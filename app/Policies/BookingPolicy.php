<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isStaff() || $booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->isStaff();
    }

    /** Owners may cancel their own active bookings; staff may cancel any. */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $booking->user_id === $user->id && $booking->status->isActive();
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->isOwner();
    }
}
