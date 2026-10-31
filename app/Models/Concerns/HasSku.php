<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Единая генерация и нормализация артикулов (SKU).
 *
 * Правила совпадают с логикой слагов (см. HasSlug):
 *  - артикул строится из релевантных полей записи (по умолчанию title);
 *  - значение транслитерируется, приводится к верхнему регистру, без пробелов;
 *  - допустимы только заглавные латинские буквы, цифры и одиночные дефисы;
 *  - при совпадении добавляется числовой суффикс: -2, -3, …;
 *  - коллизии проверяются и в собственной таблице, и в переданных источниках
 *    (например, в остатках склада), чтобы артикул был уникален в системе.
 */
trait HasSku
{
    public static function bootHasSku(): void
    {
        static::saving(function (Model $model): void {
            $sku = trim((string) $model->getAttribute('sku'));

            if ($sku === '') {
                $sku = static::uniqueSku($model->skuSource(), $model->getKey());
            }

            $model->setAttribute('sku', static::normalizeSku($sku, static::skuMaxLength()));
        });
    }

    /** Значение, из которого формируется артикул. */
    public function skuSource(): string
    {
        $parts = [];

        foreach (static::skuSourceFields() as $field) {
            $value = $this->getAttribute($field);

            if (is_scalar($value) && trim((string) $value) !== '') {
                $parts[] = (string) $value;
            }
        }

        return implode(' ', $parts);
    }

    /** @return list<string> */
    public static function skuSourceFields(): array
    {
        return ['title'];
    }

    public static function skuMaxLength(): int
    {
        return 64;
    }

    public static function skuFallback(): string
    {
        return 'TOVAR';
    }

    public static function normalizeSku(?string $value, int $maxLength = 64): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return static::skuify($value, $maxLength);
    }

    /** Транслитерация и очистка значения без учёта особенностей конкретной модели. */
    public static function skuify(string $value, int $maxLength = 64): string
    {
        $slug = trim(Str::slug($value, '-'), '-');

        if ($slug === '') {
            return '';
        }

        $sku = Str::upper($slug);
        $sku = Str::limit($sku, max(1, $maxLength), '');

        return trim($sku, '-');
    }

    public static function uniqueSku(string $source, ?int $ignoreId = null): string
    {
        $base = static::skuify($source, static::skuMaxLength());

        if ($base === '') {
            $base = static::skuFallback();
        }

        $maxLength = static::skuMaxLength();
        $candidate = $base;
        $suffix = 2;

        while (static::skuTaken($candidate, $ignoreId)) {
            $tail = '-'.$suffix++;
            $candidate = trim(Str::limit($base, max(1, $maxLength - strlen($tail)), ''), '-').$tail;
        }

        return $candidate;
    }

    protected static function skuTaken(string $sku, ?int $ignoreId = null): bool
    {
        $query = static::query()->where('sku', $sku);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            return true;
        }

        return static::skuReservedElsewhere($sku, $ignoreId);
    }

    /**
     * Дополнительные источники, в которых артикул не должен повторяться
     * (например, остатки склада). По умолчанию проверок нет.
     */
    protected static function skuReservedElsewhere(string $sku, ?int $ignoreId): bool
    {
        return false;
    }
}
