<?php

namespace App\Models;

use App\Models\Concerns\HasMediaFiles;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'title', 'slug', 'type', 'excerpt', 'body', 'image', 'sort',
    'is_published', 'seo_title', 'seo_description', 'published_at',
])]
class ContentPage extends Model
{
    use HasMediaFiles, HasSlug;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort' => 'integer',
        ];
    }

    public function sitePage(): MorphOne
    {
        return $this->morphOne(SitePage::class, 'source');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Только страницы, не находящиеся на техническом обслуживании:
     * активность хранится в общем реестре site_pages.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('sitePage', fn ($query) => $query->where('is_active', true));
    }

    public static function slugRouteName(): ?string
    {
        return 'guides.show';
    }

    public static function slugFallback(): string
    {
        return 'material';
    }
}
