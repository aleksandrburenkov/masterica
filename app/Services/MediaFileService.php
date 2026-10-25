<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaFileService
{
    /**
     * @return array{deleted: list<string>, skipped: list<string>, failed: list<string>}
     */
    public function deleteForModel(Model $model): array
    {
        return $this->deletePaths(
            $this->pathsForModel($model),
            $model::class,
            $model->getKey(),
        );
    }

    /**
     * @return list<string>
     */
    public function pathsForModel(Model $model): array
    {
        if (! method_exists($model, 'mediaAttributes')) {
            return [];
        }

        $paths = [];

        foreach ($model->mediaAttributes() as $attribute) {
            if (! is_string($attribute) || $attribute === '') {
                continue;
            }

            foreach ($this->flatten($model->getAttribute($attribute)) as $path) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<int, mixed>  $paths
     * @return array{deleted: list<string>, skipped: list<string>, failed: list<string>}
     */
    public function deletePaths(array $paths, ?string $exceptClass = null, mixed $exceptKey = null): array
    {
        $result = ['deleted' => [], 'skipped' => [], 'failed' => []];
        $disk = Storage::disk((string) config('media.disk', 'public'));

        foreach (array_unique($paths) as $rawPath) {
            $path = $this->normalizePath($rawPath);

            if ($path === null || ! $this->isManagedPath($path)) {
                $result['skipped'][] = (string) $rawPath;

                continue;
            }

            if ($this->isReferencedElsewhere($path, $exceptClass, $exceptKey)) {
                $result['skipped'][] = $path;

                continue;
            }

            try {
                if (! $disk->exists($path)) {
                    $result['skipped'][] = $path;

                    continue;
                }

                if ($disk->delete($path)) {
                    $result['deleted'][] = $path;
                } else {
                    $result['failed'][] = $path;
                    Log::warning('Не удалось удалить медиафайл.', ['path' => $path, 'disk' => config('media.disk')]);
                }
            } catch (Throwable $exception) {
                $result['failed'][] = $path;
                Log::warning('Ошибка при удалении медиафайла.', [
                    'path' => $path,
                    'disk' => config('media.disk'),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    public function isReferencedElsewhere(string $path, ?string $exceptClass = null, mixed $exceptKey = null): bool
    {
        $normalized = $this->normalizePath($path);

        if ($normalized === null) {
            return false;
        }

        foreach ((array) config('media.models', []) as $modelClass => $attributes) {
            if (! is_string($modelClass) || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            /** @var Model $instance */
            $instance = new $modelClass;
            $keyName = $instance->getKeyName();

            foreach ((array) $attributes as $attribute) {
                if (! is_string($attribute) || $attribute === '') {
                    continue;
                }

                try {
                    $records = $modelClass::query()
                        ->withoutGlobalScopes()
                        ->whereNotNull($attribute)
                        ->select([$keyName, $attribute])
                        ->cursor();

                    foreach ($records as $record) {
                        if (
                            $exceptClass !== null
                            && $record::class === $exceptClass
                            && (string) $record->getKey() === (string) $exceptKey
                        ) {
                            continue;
                        }

                        foreach ($this->flatten($record->getAttribute($attribute)) as $candidate) {
                            if ($this->normalizePath($candidate) === $normalized) {
                                return true;
                            }
                        }
                    }
                } catch (Throwable $exception) {
                    Log::warning('Не удалось проверить использование медиафайла.', [
                        'path' => $path,
                        'model' => $modelClass,
                        'attribute' => $attribute,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return false;
    }

    public function normalizePath(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $path = trim($value);

        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        if (str_starts_with($path, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) === 1) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_split('/[?#]/', $path, 2)[0] ?? '';
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if ($path === '') {
            return null;
        }

        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return implode('/', $segments);
    }

    public function isManagedPath(string $path): bool
    {
        foreach ((array) config('media.protected_prefixes', []) as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix.'/'))) {
                return false;
            }
        }

        foreach ((array) config('media.managed_directories', []) as $directory) {
            $directory = trim((string) $directory, '/');

            if ($directory !== '' && str_starts_with($path, $directory.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function flatten(mixed $value): array
    {
        if (is_string($value)) {
            return $value === '' ? [] : [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $paths = [];

        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $paths[] = $item;
            }
        }

        return $paths;
    }
}
