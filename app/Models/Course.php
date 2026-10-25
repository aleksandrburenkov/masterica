<?php

namespace App\Models;

use App\Models\Concerns\HasMediaFiles;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'master_id', 'technique_id', 'title', 'slug', 'short_description',
    'description', 'outcome', 'level', 'price_cents', 'currency',
    'access_days', 'image', 'sort', 'is_published', 'seo_title', 'seo_description',
])]
class Course extends Model
{
    use HasMediaFiles, HasSlug;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'price_cents' => 'integer',
            'access_days' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function technique(): BelongsTo
    {
        return $this->belongsTo(Technique::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, CourseModule::class, 'course_id', 'course_module_id');
    }

    public function lessonsCount(): int
    {
        return $this->lessons()->count();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('sort');
    }

    public static function slugRouteName(): ?string
    {
        return 'courses.show';
    }

    public static function slugFallback(): string
    {
        return 'kurs';
    }
}
