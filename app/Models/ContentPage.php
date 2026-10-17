<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title', 'slug', 'type', 'excerpt', 'body', 'image', 'sort',
    'is_published', 'seo_title', 'seo_description', 'published_at',
])]
class ContentPage extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
