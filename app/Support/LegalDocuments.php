<?php

namespace App\Support;

use App\Models\LegalDocument;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Репозиторий правовых документов с кэшированием по образцу Settings.
 * Возвращает документы, редактируемые владельцем в админ-панели.
 */
class LegalDocuments
{
    private const CACHE_KEY = 'app.legal_documents.all';

    /** @var array<string, LegalDocument>|null */
    private static ?array $memo = null;

    /** @return array<string, LegalDocument> */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            return self::$memo = Cache::rememberForever(self::CACHE_KEY, function () {
                return LegalDocument::query()
                    ->orderBy('sort')
                    ->get()
                    ->keyBy('key')
                    ->all();
            });
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
    }
}
