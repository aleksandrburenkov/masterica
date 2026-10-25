<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'course_module_id', 'title', 'slug', 'content', 'video_path',
    'duration_minutes', 'is_preview', 'is_published', 'sort',
])]
class Lesson extends Model
{
    use HasSlug;

    protected function casts(): array
    {
        return [
            'is_preview' => 'boolean',
            'is_published' => 'boolean',
            'duration_minutes' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function course(): Course
    {
        return $this->module->course;
    }

    /** Whether a lesson can be viewed without an active enrollment. */
    public function isFree(): bool
    {
        return $this->is_preview;
    }

    public function slugScope(): array
    {
        return ['course_module_id' => $this->course_module_id];
    }

    public static function slugMaxLength(): int
    {
        return 120;
    }

    public static function slugFallback(): string
    {
        return 'urok';
    }
}
