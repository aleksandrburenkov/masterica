<?php

namespace App\Models;

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
     * недопустим символ «/»: иначе адрес распадается на два сегмента и
     * Laravel не находит маршрут (404). Приводим значение к безопасному виду
     * на уровне модели — это защищает и код (сидеры, импорт), и формы.
     */
    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): string => self::normalizeSlug($value),
        );
    }

    public static function normalizeSlug(?string $value): string
    {
        $value = trim((string) $value);

        // «/dokumenty/politics» → «politics»: оставляем только последний сегмент пути.
        $value = Str::afterLast($value, '/');

        // Транслитерация кириллицы, пробелы и подчёркивания → дефисы.
        return Str::slug($value);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeProductionReady($query)
    {
        return $query->where('is_production_ready', true);
    }
}
