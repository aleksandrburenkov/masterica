<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'technique_id', 'title', 'slug', 'short_description', 'description',
    'price_cents', 'currency', 'image', 'gallery', 'sort', 'is_published',
    'seo_title', 'seo_description',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'price_cents' => 'integer',
            'gallery' => 'array',
            'sort' => 'integer',
        ];
    }

    public function technique(): BelongsTo
    {
        return $this->belongsTo(Technique::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('sort');
    }

    public function minPriceCents(): int
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

        return (int) $variants->min('price_cents') ?: $this->price_cents;
    }

    public function totalStock(): int
    {
        return (int) $this->variants()->sum('stock');
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($title), 180, '');
        $base = $base !== '' ? $base : 'tovar';

        if (! static::slugExists($base, $ignoreId)) {
            return $base;
        }

        $token = static::slugToken($title);
        $candidate = "{$base}-{$token}";

        if (! static::slugExists($candidate, $ignoreId)) {
            return $candidate;
        }

        $index = 1;

        do {
            $candidate = "{$base}-{$token}-".static::alphabeticIndex($index++);
        } while (static::slugExists($candidate, $ignoreId));

        return $candidate;
    }

    private static function slugExists(string $slug, ?int $ignoreId): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    private static function slugToken(string $title): string
    {
        $words = array_filter(explode('-', Str::slug($title)));
        $token = '';

        foreach (array_slice($words, 0, 4) as $word) {
            $token .= Str::substr($word, 0, 1);
        }

        return $token !== '' ? $token : 'tovar';
    }

    private static function alphabeticIndex(int $index): string
    {
        $suffix = '';

        while ($index > 0) {
            $index--;
            $suffix = chr(97 + ($index % 26)).$suffix;
            $index = intdiv($index, 26);
        }

        return $suffix;
    }
}
