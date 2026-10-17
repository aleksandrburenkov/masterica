<?php

namespace App\Policies;

use App\Models\CourseEnrollment;
use App\Models\User;

class CourseEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, CourseEnrollment $enrollment): bool
    {
        return $user->isStaff() || $enrollment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CourseEnrollment $enrollment): bool
    {
        return $user->isStaff();
    }

    /** Owners may revoke their own access (idempotent, audited). */
    public function revoke(User $user, CourseEnrollment $enrollment): bool
    {
        return $user->isStaff() || $enrollment->user_id === $user->id;
    }

    public function delete(User $user, CourseEnrollment $enrollment): bool
    {
        return $user->isOwner();
    }
}
