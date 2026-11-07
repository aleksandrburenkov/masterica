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
        // Текст документа выводится на сайте без экранирования, поэтому
        // очищаем HTML при любом сохранении — и из админки, и из сидеров.
        static::saving(function (LegalDocument $document): void {
            if (! $document->isDirty('body')) {
                return;
            }

            $body = trim((string) $document->body);
            $body = $body === '' ? null : Str::sanitizeHtml($body);

            // Редактор сериализует блоки без переносов строк. Если разметка
            // семантически не изменилась, оставляем исходное форматирование,
            // чтобы сохранение без правок не переписывало существующие документы.
            $originalBody = $document->getOriginal('body');

            if (
                $body !== null
                && is_string($originalBody)
                && $originalBody !== ''
                && self::htmlSignature($body) === self::htmlSignature($originalBody)
            ) {
                $body = $originalBody;
            }

            $document->body = $body;
        });

        static::saved(fn () => LegalDocuments::flush());
        static::deleted(fn () => LegalDocuments::flush());
    }

    /**
     * Безопасный HTML для вывода на публичной странице.
     *
     * Повторная очистка нужна для документов, сохранённых до включения
     * санитайзера: они могли попасть в базу с небезопасной разметкой.
     */
    public function safeBody(): string
    {
        return Str::sanitizeHtml((string) $this->body);
    }

    /**
     * Семантическая подпись HTML без пробелов между тегами.
     * Нужна только для сравнения «до/после» при сохранении.
     */
    private static function htmlSignature(string $html): string
    {
        return preg_replace('/>\s+</u', '><', trim($html)) ?? trim($html);
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
