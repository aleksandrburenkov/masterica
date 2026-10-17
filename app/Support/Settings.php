<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Small typed settings repository backed by the `settings` table.
 * Values are editable in Filament and cached to avoid repeated queries.
 */
class Settings
{
    /** In-request and cross-request cache key. */
    private const CACHE_KEY = 'app.settings.all';

    /** @var array<string, string|null>|null */
    private static ?array $memo = null;

    /** @return array<string, string|null> */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        return self::$memo = Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::all()[$key] ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value === null || $value === '' ? $default : (int) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return $value === null ? $default : (bool) (int) $value;
    }

    public static function set(string $key, ?string $value, string $group = 'general', string $type = 'string'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type],
        );

        self::flush();
    }

    public static function flush(): void
    {
        self::$memo = null;
        Cache::forget(self::CACHE_KEY);
    }
}
