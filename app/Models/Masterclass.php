<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'technique_id', 'title', 'slug', 'short_description', 'description', 'outcome',
    'level', 'age_min', 'age_max', 'duration_minutes', 'price_cents',
    'outreach_price_cents', 'included_participants', 'participant_surcharges', 'currency',
    'materials_included', 'format', 'image', 'sort', 'is_published',
    'seo_title', 'seo_description',
])]
class Masterclass extends Model
{
    protected function casts(): array
    {
        return [
            'materials_included' => 'boolean',
            'is_published' => 'boolean',
            'age_min' => 'integer',
            'age_max' => 'integer',
            'duration_minutes' => 'integer',
            'price_cents' => 'integer',
            'outreach_price_cents' => 'integer',
            'included_participants' => 'integer',
            'participant_surcharges' => 'array',
            'sort' => 'integer',
        ];
    }

    public function technique(): BelongsTo
    {
        return $this->belongsTo(Technique::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(WorkshopSession::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function priceRub(): float
    {
        return $this->price_cents / 100;
    }

    /**
     * Цена выездного мастер-класса за человека. Если отдельная цена не задана,
     * используется обычная цена мастер-класса.
     */
    public function outreachPriceCents(): int
    {
        return (int) ($this->outreach_price_cents ?? $this->price_cents);
    }

    public function durationLabel(): string
    {
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;
        $parts = [];
        if ($h > 0) {
            $parts[] = $h.' ч';
        }
        if ($m > 0) {
            $parts[] = $m.' мин';
        }

        return implode(' ', $parts) ?: '—';
    }

    public function ageLabel(): string
    {
        if ($this->age_max !== null) {
            return $this->age_min.'–'.$this->age_max.' лет';
        }

        return $this->age_min.'+';
    }
}
