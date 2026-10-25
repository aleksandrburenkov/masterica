<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'technique_id', 'title', 'slug', 'short_description', 'description',
    'price_cents', 'currency', 'image', 'gallery', 'sort', 'is_published',
    'seo_title', 'seo_description',
])]
class Product extends Model
{
    use HasSlug;

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

    public static function slugRouteName(): ?string
    {
        return 'shop.show';
    }

    public static function slugFallback(): string
    {
        return 'tovar';
    }
}
