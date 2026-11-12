<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use App\Support\SitePages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Прячет содержимое страниц сайта от посетителей в режиме модернизации.
 *
 * Два уровня:
 *  - глобальный режим (настройка pages.maintenance_enabled) скрывает все
 *    страницы сайта одним действием;
 *  - точечный режим скрывает отдельные страницы из реестра site_pages.
 *
 * Владелец сайта (авторизованный администратор) всегда видит сайт целиком —
 * и при глобальном режиме, и при скрытых отдельных страницах. Служебные
 * маршруты (вход, регистрация, админ-панель, sitemap/robots) не блокируются,
 * чтобы владелец мог войти и выключить режим.
 */
class EnsurePageIsActive
{
    /** Маршруты, доступные даже при глобальном режиме. */
    private const EXEMPT_ROUTES = [
        'login',
        'login.store',
        'register',
        'register.store',
        'sitemap',
        'robots',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        if ($this->isSiteOwner($request)) {
            return $next($request);
        }

        if ($this->isGlobalMaintenanceEnabled() && ! $this->isExempt($request)) {
            return $this->maintenanceResponse('Сайт на техническом обслуживании');
        }

        $page = SitePages::findForRequest($request);

        if ($page !== null && ! $page->is_active) {
            return $this->maintenanceResponse();
        }

        return $next($request);
    }

    /** Владелец сайта (администратор) видит сайт в любом режиме. */
    private function isSiteOwner(Request $request): bool
    {
        return $request->user()?->role?->canManageSettings() ?? false;
    }

    private function isGlobalMaintenanceEnabled(): bool
    {
        return Settings::bool('pages.maintenance_enabled');
    }

    /** Служебные маршруты не участвуют в глобальном скрытии. */
    private function isExempt(Request $request): bool
    {
        if ($request->is('admin', 'admin/*', 'up')) {
            return true;
        }

        $name = $request->route()?->getName();

        return $name !== null && in_array($name, self::EXEMPT_ROUTES, true);
    }

    private function maintenanceResponse(?string $heading = null): Response
    {
        return response()->view('pages.maintenance', [
            'heading' => $heading ?? 'Страница временно недоступна',
        ], 503);
    }
}
