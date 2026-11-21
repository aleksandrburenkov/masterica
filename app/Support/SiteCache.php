<?php

namespace App\Support;

use BladeUI\Icons\IconsManifest;
use Filament\Facades\Filament;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Ручной сброс кеша сайта из админ-панели.
 *
 * Автоматическое кеширование (настройки, правовые документы, геокодер)
 * продолжает работать как прежде. Этот класс даёт владельцу возможность
 * принудительно пересобрать выбранный слой кеша, не дожидаясь истечения TTL.
 */
class SiteCache
{
    public const GEOCODE_PREFIX = 'geocode.';

    /** Полный сброс данных приложения: настройки, документы, геокодер и т.п. */
    public static function clearData(): void
    {
        Cache::flush();

        self::resetRepositories();
    }

    public static function clearSettings(): void
    {
        Settings::flush();
    }

    public static function clearLegalDocuments(): void
    {
        LegalDocuments::flush();
    }

    /**
     * Удаляет только ключи геокодера, не затрагивая остальной кеш.
     *
     * @return int Количество удалённых записей.
     */
    public static function clearGeocoding(): int
    {
        $store = Cache::store()->getStore();

        if (! $store instanceof DatabaseStore) {
            throw new RuntimeException('Выборочная очистка кеша адресов доступна только при драйвере кеша database.');
        }

        $table = (string) config('cache.stores.'.config('cache.default').'.table', 'cache');

        $prefixes = [
            $store->getPrefix().self::GEOCODE_PREFIX,
            $store->getPrefix().CacheRepository::FLEXIBLE_CREATED_KEY_PREFIX.self::GEOCODE_PREFIX,
        ];

        return $store->getConnection()
            ->table($table)
            ->where(function ($query) use ($prefixes): void {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('key', 'like', $prefix.'%');
                }
            })
            ->delete();
    }

    public static function canClearGeocoding(): bool
    {
        return Cache::store()->getStore() instanceof DatabaseStore;
    }

    public static function clearViews(): void
    {
        self::run('view:clear');
    }

    public static function clearConfig(): void
    {
        self::run('config:clear');
    }

    public static function clearRoutes(): void
    {
        self::run('route:clear');
    }

    public static function clearEvents(): void
    {
        self::run('event:clear');
    }

    /**
     * Команда `filament:optimize-clear` внутри вызывает `icons:clear`,
     * который регистрируется только в консольном режиме. Поэтому в веб-запросе
     * из админки очищаем компоненты и манифест иконок напрямую.
     */
    public static function clearFilament(): void
    {
        foreach (Filament::getPanels() as $panel) {
            $panel->clearCachedComponents();
        }

        app(IconsManifest::class)->delete();
    }

    /** @return bool false, если OPcache отключён или недоступен. */
    public static function clearOpcache(): bool
    {
        return function_exists('opcache_reset') && @opcache_reset();
    }

    public static function clearAll(): void
    {
        self::run('optimize:clear');

        self::clearFilament();
        self::resetRepositories();

        self::clearOpcache();
    }

    private static function resetRepositories(): void
    {
        Settings::flush();
        LegalDocuments::flush();
    }

    private static function run(string $command): void
    {
        $exitCode = Artisan::call($command);

        if ($exitCode !== 0) {
            $output = trim(Artisan::output());

            throw new RuntimeException($output !== '' ? $output : "Команда {$command} завершилась с кодом {$exitCode}.");
        }
    }
}
