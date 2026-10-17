<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_enrollment_id', 'lesson_id', 'completed_at'])]
class LessonProgress extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
