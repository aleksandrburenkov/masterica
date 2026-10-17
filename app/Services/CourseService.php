<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function enrollmentFor(User $user, Course $course): ?CourseEnrollment
    {
        return CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
    }

    /** Create (idempotently) a pending enrollment awaiting payment. */
    public function enroll(User $user, Course $course): CourseEnrollment
    {
        return CourseEnrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['status' => EnrollmentStatus::PendingPayment, 'payment_status' => PaymentStatus::Unpaid],
        );
    }

    /**
     * Local test-only action: activate access without any real charge.
     * Models the outcome of a completed payment.
     */
    public function grantTestPayment(User $user, Course $course, ?User $actor = null): CourseEnrollment
    {
        $enrollment = $this->enroll($user, $course);

        return $this->grantAccess($enrollment, $actor, 'Тестовая выдача доступа (без реального списания)');
    }

    public function grantAccess(CourseEnrollment $enrollment, ?User $actor = null, ?string $reason = null): CourseEnrollment
    {
        if ($enrollment->status === EnrollmentStatus::Active) {
            return $enrollment;
        }

        $enrollment->forceFill([
            'status' => EnrollmentStatus::Active,
            'payment_status' => PaymentStatus::Paid,
            'granted_at' => now(),
            'expires_at' => now()->addDays($enrollment->course->access_days),
            'revoked_at' => null,
            'revoked_by' => null,
            'revoke_reason' => null,
        ])->save();

        $this->audit->log($actor, 'enrollment.granted', $enrollment, $reason);
        $this->notifications->notify(
            $enrollment->user,
            'Доступ к курсу открыт',
            'Курс «'.$enrollment->course->title.'» доступен в личном кабинете.',
        );

        return $enrollment;
    }

    public function revoke(CourseEnrollment $enrollment, ?User $actor = null, ?string $reason = null): CourseEnrollment
    {
        if ($enrollment->status === EnrollmentStatus::Revoked) {
            return $enrollment;
        }

        $enrollment->forceFill([
            'status' => EnrollmentStatus::Revoked,
            'revoked_at' => now(),
            'revoked_by' => $actor?->id,
            'revoke_reason' => $reason,
        ])->save();

        $this->audit->log($actor, 'enrollment.revoked', $enrollment, $reason);

        return $enrollment;
    }

    /** Server-side check used before serving any paid lesson material. */
    public function hasAccess(User $user, Lesson $lesson): bool
    {
        if ($lesson->isFree()) {
            return true;
        }

        $course = $lesson->module->course;
        $enrollment = $this->enrollmentFor($user, $course);

        return $enrollment !== null && $enrollment->hasActiveAccess();
    }

    public function markLessonComplete(CourseEnrollment $enrollment, Lesson $lesson): LessonProgress
    {
        if (! $enrollment->hasActiveAccess()) {
            throw ValidationException::withMessages(['lesson' => 'Доступ к курсу не активен.']);
        }

        if ($lesson->module->course_id !== $enrollment->course_id) {
            throw ValidationException::withMessages(['lesson' => 'Урок не относится к этому курсу.']);
        }

        $progress = LessonProgress::updateOrCreate(
            ['course_enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now(), 'created_at' => now()],
        );

        $this->syncCompletion($enrollment);

        return $progress;
    }

    public function syncCompletion(CourseEnrollment $enrollment): void
    {
        if ($enrollment->status !== EnrollmentStatus::Active) {
            return;
        }

        $total = Lesson::query()
            ->whereHas('module', fn ($q) => $q->where('course_id', $enrollment->course_id))
            ->where('is_published', true)
            ->count();

        $done = LessonProgress::query()
            ->where('course_enrollment_id', $enrollment->id)
            ->whereNotNull('completed_at')
            ->count();

        if ($total > 0 && $done >= $total) {
            $enrollment->forceFill([
                'status' => EnrollmentStatus::Completed,
                'completed_at' => now(),
            ])->save();
        }
    }

    public function progressPercent(CourseEnrollment $enrollment): int
    {
        $total = Lesson::query()
            ->whereHas('module', fn ($q) => $q->where('course_id', $enrollment->course_id))
            ->where('is_published', true)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $done = LessonProgress::query()
            ->where('course_enrollment_id', $enrollment->id)
            ->whereNotNull('completed_at')
            ->count();

        return (int) round($done / $total * 100);
    }
}
