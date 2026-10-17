<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Review $review): bool
    {
        return $user->isStaff() || $review->user_id === $user->id;
    }

    /** Only authenticated clients may submit a review. */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Review $review): bool
    {
        return $user->isStaff();
    }

    /** Moderation is a staff action. */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isStaff();
    }
}
