<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'name', 'sku', 'price_cents', 'stock', 'is_active'])]
class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_cents' => 'integer',
            'stock' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id');
    }

    public function inStock(): bool
    {
        return $this->is_active && $this->stock > 0;
    }
}
