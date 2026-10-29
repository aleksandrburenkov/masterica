<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;

/**
 * Модерация отзывов.
 *
 * Единственная точка принятия решения о публикации: при одобрении видео
 * публикуется (файл переносится, при необходимости сжимается), при отклонении
 * видео удаляется полностью — текстовый отзыв остаётся без изменений.
 */
class ReviewModerationService
{
    public function __construct(
        private readonly ReviewVideoService $videos,
        private readonly AuditLogger $audit,
    ) {}

    public function approve(Review $review, ?User $moderator = null): Review
    {
        $review->forceFill([
            'status' => ReviewStatus::Approved,
            'published_at' => $review->published_at ?? now(),
            'moderated_by' => $moderator?->id,
            'moderated_at' => now(),
        ])->save();

        $this->videos->publishForReview($review);

        $this->audit->log($moderator, 'review.approved', $review, null, [
            'status' => ReviewStatus::Approved->value,
        ]);

        return $review;
    }

    public function reject(Review $review, ?User $moderator = null, ?string $reason = null): Review
    {
        // При отклонении видео удаляется сразу и бесследно; текст отзыва остаётся.
        $this->videos->deleteForReview($review);

        $review->forceFill([
            'status' => ReviewStatus::Rejected,
            'moderated_by' => $moderator?->id,
            'moderated_at' => now(),
        ])->save();

        $this->audit->log($moderator, 'review.rejected', $review, $reason, [
            'status' => ReviewStatus::Rejected->value,
        ]);

        return $review;
    }

    /**
     * Применяет последствия смены статуса (используется формой админки, где
     * статус можно изменить вручную).
     */
    public function applyStatus(Review $review, ReviewStatus $status, ?User $moderator = null): Review
    {
        return match ($status) {
            ReviewStatus::Approved => $this->approve($review, $moderator),
            ReviewStatus::Rejected => $this->reject($review, $moderator),
            ReviewStatus::Pending => $review,
        };
    }
}
