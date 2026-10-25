<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'sort', 'is_active'])]
class Technique extends Model
{
    use HasSlug;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public static function slugSourceFields(): array
    {
        return ['name'];
    }

    public static function slugMaxLength(): int
    {
        return 120;
    }

    public static function slugFallback(): string
    {
        return 'texnika';
    }

    public function masterclasses(): HasMany
    {
        return $this->hasMany(Masterclass::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
