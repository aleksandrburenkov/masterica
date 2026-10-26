<?php

namespace App\Models;

use App\Models\Concerns\HasMediaFiles;
use App\Models\Concerns\HasSlug;
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
    use HasMediaFiles, HasSlug;

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

    /**
     * Есть ли у мастер-класса опубликованные будущие сеансы. Условие совпадает
     * с выборкой сеансов на странице мастер-класса, поэтому кнопка «Оставить
     * запрос» и режим запроса в форме согласованы.
     */
    public function hasActiveSessions(): bool
    {
        return $this->sessions()
            ->where('is_published', true)
            ->where('starts_at', '>', now())
            ->exists();
    }

    /**
     * Подпись запроса для мастер-класса без активных сеансов. Название берётся
     * из базы (не из запроса клиента), управляющие символы и переводы строк
     * удаляются, чтобы значение нельзя было использовать для инъекций.
     */
    public function requestLabel(): string
    {
        $title = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $this->title) ?? '';
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? '');

        return $title !== '' ? 'Запрос-'.$title : 'Запрос';
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

    public static function slugRouteName(): ?string
    {
        return 'masterclasses.show';
    }

    public static function slugFallback(): string
    {
        return 'master-klass';
    }
}
