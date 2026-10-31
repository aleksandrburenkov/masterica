<?php

namespace App\Models;

use App\Models\Concerns\HasMediaFiles;
use App\Models\Concerns\HasSku;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'technique_id', 'title', 'slug', 'sku', 'short_description', 'sizes', 'description',
    'price_cents', 'sale_price_cents', 'currency', 'image', 'gallery', 'sort', 'is_published',
    'seo_title', 'seo_description',
])]
class Product extends Model
{
    use HasMediaFiles, HasSku, HasSlug;

    public function mediaAttributes(): array
    {
        return ['image', 'gallery'];
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'price_cents' => 'integer',
            'sale_price_cents' => 'integer',
            'gallery' => 'array',
            'sort' => 'integer',
        ];
    }

    /**
     * Артикул товара уникален и дублируется в остатках склада, поэтому
     * проверяем коллизии и среди артикулов складских записей.
     */
    protected static function skuReservedElsewhere(string $sku, ?int $ignoreId): bool
    {
        return ProductVariant::query()
            ->where('sku', $sku)
            ->when($ignoreId !== null, fn ($query) => $query->where('product_id', '!=', $ignoreId))
            ->exists();
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

    /**
     * Есть ли действующая акционная цена. Акция учитывается, только если она
     * заполнена и действительно ниже базовой цены.
     */
    public function hasSalePrice(): bool
    {
        return $this->sale_price_cents !== null
            && (int) $this->sale_price_cents > 0
            && (int) $this->sale_price_cents < (int) $this->price_cents;
    }

    /** Цена, по которой товар продаётся: акционная при наличии, иначе базовая. */
    public function effectivePriceCents(): int
    {
        return $this->hasSalePrice() ? (int) $this->sale_price_cents : (int) $this->price_cents;
    }

    /** Единственная складская запись товара (товар без вариативности). */
    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants()->orderBy('id')->first();
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
