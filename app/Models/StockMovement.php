<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['variant_id', 'type', 'quantity', 'reason', 'actor_id', 'reference_type', 'reference_id'])]
class StockMovement extends Model
{
    /** Movements are append-only: no updated_at column. */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement) {
            $movement->created_at ??= now();
        });
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
