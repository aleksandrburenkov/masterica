<?php

namespace App\Support;

use App\Models\LegalDocument;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Репозиторий правовых документов с кэшированием по образцу Settings.
 * Возвращает документы, редактируемые владельцем в админ-панели.
 *
 * В кэш кладём только «сырые» массивы атрибутов, а не Eloquent-модели:
 * сериализация моделей приводит к тому, что при чтении кэша (особенно после
 * изменения кода/перезапуска PHP) объекты восстанавливаются как
 * __PHP_Incomplete_Class и падают при обращении к свойствам.
 */
class LegalDocuments
{
    private const CACHE_KEY = 'app.legal_documents.attributes';

    /** @var array<string, LegalDocument>|null */
    private static ?array $memo = null;

    /** @return array<string, LegalDocument> */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            $attributes = Cache::rememberForever(self::CACHE_KEY, function (): array {
                return LegalDocument::query()
                    ->orderBy('sort')
                    ->get()
                    ->map(fn (LegalDocument $document): array => $document->getAttributes())
                    ->all();
            });

            return self::$memo = LegalDocument::hydrate($attributes)->keyBy('key')->all();
        } catch (Throwable) {
            // До выполнения миграций таблицы может не быть — не ломаем приложение.
            return self::$memo = [];
        }
    }

    public static function get(string $key): ?LegalDocument
    {
        return self::all()[$key] ?? null;
    }

    public static function field(string $key, string $field, ?string $default = null): ?string
    {
        $value = self::get($key)?->{$field};

        return $value === null || $value === '' ? $default : (string) $value;
    }

    public static function flush(): void
    {
        self::$memo = null;

        Cache::forget(self::CACHE_KEY);
        // Чистим и старый ключ, где могли лежать сериализованные модели.
        Cache::forget('app.legal_documents.all');
    }
}
