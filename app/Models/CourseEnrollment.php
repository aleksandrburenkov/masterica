<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'course_id', 'status', 'payment_status', 'granted_at',
    'expires_at', 'completed_at', 'revoked_at', 'revoked_by', 'revoke_reason',
])]
class CourseEnrollment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'payment_status' => PaymentStatus::class,
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /** Server-side check that access is currently valid. */
    public function hasActiveAccess(): bool
    {
        return $this->status->givesAccess()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
