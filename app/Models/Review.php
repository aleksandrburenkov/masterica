<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'masterclass_id', 'type', 'rating', 'body', 'video_path',
    'city', 'is_demo', 'status', 'consent_data', 'consent_publish', 'consent_marketing',
    'source', 'published_at', 'moderated_by', 'moderated_at',
])]
class Review extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ReviewType::class,
            'status' => ReviewStatus::class,
            'is_demo' => 'boolean',
            'consent_data' => 'boolean',
            'consent_publish' => 'boolean',
            'consent_marketing' => 'boolean',
            'rating' => 'integer',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // При удалении отзыва удаляем и связанный видеофайл с диска.
        // Связь удаляется явно, чтобы сработала очистка файлов (FK-каскад
        // в базе события модели не вызывает).
        static::deleting(function (Review $review): void {
            $review->video()->first()?->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): HasOne
    {
        return $this->hasOne(ReviewVideo::class);
    }

    public function masterclass(): BelongsTo
    {
        return $this->belongsTo(Masterclass::class);
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', ReviewStatus::Approved->value);
    }

    public function scopeDemo($query)
    {
        return $query->where('is_demo', true);
    }
}
