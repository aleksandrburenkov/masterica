<?php

namespace App\Models\Concerns;

use App\Exceptions\SlugMovedException;
use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Единая генерация и нормализация слагов.
 *
 * Правила:
 *  - слаг строится из релевантных полей записи (по умолчанию title, у техник — name);
 *  - значение транслитерируется в латиницу и приводится к нижнему регистру;
 *  - допустимы только латинские буквы, цифры и одиночные дефисы;
 *  - при совпадении добавляется числовой суффикс: -2, -3, …;
 *  - при смене слага сохраняется история для 301-редиректа со старого URL.
 */
trait HasSlug
{
    protected ?string $slugBeforeSave = null;

    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            $slug = trim((string) $model->getAttribute('slug'));

            if ($slug === '') {
                $slug = static::uniqueSlug(
                    $model->slugSource(),
                    $model->getKey(),
                    $model->slugScope(),
                );
            }

            $model->setAttribute('slug', static::normalizeSlug($slug, static::slugMaxLength()));
        });

        static::created(function (Model $model): void {
            SlugRedirect::forget(static::class, (string) $model->getAttribute('slug'));
        });

        static::updating(function (Model $model): void {
            $model->slugBeforeSave = $model->getOriginal('slug');
        });

        static::updated(function (Model $model): void {
            $oldSlug = $model->slugBeforeSave;
            $model->slugBeforeSave = null;

            if (blank($oldSlug) || $oldSlug === $model->getAttribute('slug')) {
                return;
            }

            SlugRedirect::record(static::class, (string) $oldSlug, (string) $model->getAttribute('slug'));
        });
    }

    /** Значение, из которого формируется слаг. */
    public function slugSource(): string
    {
        $parts = [];

        foreach (static::slugSourceFields() as $field) {
            $value = $this->getAttribute($field);

            if (is_scalar($value) && trim((string) $value) !== '') {
                $parts[] = (string) $value;
            }
        }

        return implode(' ', $parts);
    }

    /** @return list<string> */
    public static function slugSourceFields(): array
    {
        return ['title'];
    }

    /**
     * Колонки, в рамках которых слаг должен быть уникален.
     * Например, урок уникален внутри модуля курса.
     *
     * @return array<string, mixed>
     */
    public function slugScope(): array
    {
        return [];
    }

    public static function slugMaxLength(): int
    {
        return 180;
    }

    public static function slugFallback(): string
    {
        return 'zapis';
    }

    public static function normalizeSlug(?string $value, int $maxLength = 180): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // «/magazin/tovar» → «tovar»: слаг — это последний сегмент адреса.
        // Одиночный «/» внутри названия считается разделителем слов.
        if (str_starts_with($value, '/')) {
            $value = Str::afterLast($value, '/');
        }

        return static::slugify($value, $maxLength);
    }

    /** Транслитерация и очистка значения без учёта особенностей конкретной модели. */
    public static function slugify(string $value, int $maxLength = 180): string
    {
        $slug = trim(Str::slug($value, '-'), '-');

        if ($slug === '') {
            return '';
        }

        $slug = Str::limit($slug, max(1, $maxLength), '');

        return trim($slug, '-');
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    public static function uniqueSlug(string $source, ?int $ignoreId = null, array $scope = []): string
    {
        $base = static::slugify($source, static::slugMaxLength());

        if ($base === '') {
            $base = static::slugFallback();
        }

        $maxLength = static::slugMaxLength();
        $candidate = $base;
        $suffix = 2;

        while (static::slugTaken($candidate, $ignoreId, $scope)) {
            $tail = '-'.$suffix++;
            $candidate = trim(Str::limit($base, max(1, $maxLength - strlen($tail)), ''), '-').$tail;
        }

        return $candidate;
    }

    /** @param  array<string, mixed>  $scope */
    protected static function slugTaken(string $slug, ?int $ignoreId = null, array $scope = []): bool
    {
        $query = static::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        foreach ($scope as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query->where($column, $value);
        }

        return $query->exists();
    }

    /** Имя публичного маршрута для 301-редиректа; null, если публичного URL нет. */
    public static function slugRouteName(): ?string
    {
        return null;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $record = $this->resolveRouteBindingQuery($this, $value, $field)->first();

        if ($record) {
            return $record;
        }

        $url = static::redirectUrlFor($value, $field);

        if ($url !== null) {
            throw new SlugMovedException($url);
        }

        return null;
    }

    protected static function redirectUrlFor(mixed $value, string $field): ?string
    {
        $routeName = static::slugRouteName();

        if ($field !== 'slug' || $routeName === null || ! app()->bound('request')) {
            return null;
        }

        $request = request();

        // Редиректим только безопасные публичные GET/HEAD-запросы. POST-формы
        // и админка не должны молча менять адрес назначения.
        if (! $request->isMethodSafe() || $request->is('admin') || $request->is('admin/*') || $request->is('livewire*')) {
            return null;
        }

        $newSlug = SlugRedirect::query()
            ->where('model_type', static::class)
            ->where('old_slug', (string) $value)
            ->value('new_slug');

        if ($newSlug === null || $newSlug === (string) $value) {
            return null;
        }

        return route($routeName, $newSlug, false);
    }
}
