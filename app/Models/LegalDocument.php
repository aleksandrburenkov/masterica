<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Support\LegalDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Правовой документ платформы (политика, согласие, оферта, лицензия).
 * Тексты редактируются владельцем в админ-панели (Filament), см. LegalDocumentResource.
 */
#[Fillable([
    'key', 'slug', 'title', 'lead', 'body', 'version',
    'effective_date', 'is_production_ready', 'sort',
])]
class LegalDocument extends Model
{
    use HasSlug;

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'is_production_ready' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => LegalDocuments::flush());
        static::deleted(fn () => LegalDocuments::flush());
    }

    /**
     * Технический ключ неизменяем после создания и служит именем документа в коде
     * (legal('privacy_policy'), ссылки в футере и на формах). Лишние пробелы по краям
     * ломают поиск по ключу, поэтому обрезаем их при любом сохранении.
     */
    protected function key(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): string => trim((string) $value),
        );
    }

    /**
     * Слаг — это последний сегмент публичного адреса (/dokumenty/{slug}).
     * Он же служит ключом маршрута в админке (/{record}/edit), поэтому в нём
     * недопустим символ «/». Нормализацию выполняет трейт HasSlug: она
     * защищает и код (сидеры, импорт), и формы.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Для документа допустим только последний сегмент пути:
     * «dokumenty/politics» → «politics».
     */
    public static function normalizeSlug(?string $value, int $maxLength = 180): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return static::slugify(Str::afterLast($value, '/'), $maxLength);
    }

    public static function slugRouteName(): ?string
    {
        return 'legal.show';
    }

    public static function slugMaxLength(): int
    {
        return 120;
    }

    public static function slugFallback(): string
    {
        return 'dokument';
    }

    public function scopeProductionReady($query)
    {
        return $query->where('is_production_ready', true);
    }
}
