<?php

namespace App\Support;

use App\Models\ContentPage;
use App\Models\LegalDocument;
use App\Models\SitePage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Реестр публичных страниц сайта.
 *
 * Состоит из трёх источников:
 *  - статические страницы маршрутов (главная, о студии, контакты и т.д.),
 *    описаны в {@see self::registry()} и хранятся в site_pages по ключу;
 *  - материалы (ContentPage) и правовые документы (LegalDocument) —
 *    динамические страницы, связанные с site_pages полиморфно и
 *    синхронизируемые наблюдателями.
 *
 * Раздел «Настройки» → «Страницы сайта» показывает единый список, а
 * middleware EnsurePageIsActive удерживает неактивные страницы в режиме
 * «технического обслуживания».
 */
class SitePages
{
    /**
     * Статические страницы сайта.
     *
     * @return array<int, array{key:string,title:string,group:string,route:string,sort:int}>
     */
    public static function registry(): array
    {
        return [
            ['key' => 'home', 'title' => 'Главная', 'group' => 'Основное', 'route' => 'home', 'sort' => 10],

            ['key' => 'about', 'title' => 'О студии', 'group' => 'Информация', 'route' => 'about', 'sort' => 20],
            ['key' => 'how-classes-work', 'title' => 'Как проходят занятия', 'group' => 'Информация', 'route' => 'how-classes-work', 'sort' => 30],
            ['key' => 'contacts', 'title' => 'Контакты', 'group' => 'Информация', 'route' => 'contacts', 'sort' => 40],

            ['key' => 'masterclasses.index', 'title' => 'Мастер-классы', 'group' => 'Мастер-классы', 'route' => 'masterclasses.index', 'sort' => 50],
            ['key' => 'masterclasses.bryansk', 'title' => 'Мастер-классы в Брянске', 'group' => 'Мастер-классы', 'route' => 'masterclasses.bryansk', 'sort' => 60],
            ['key' => 'outreach.index', 'title' => 'Выездные мастер-классы', 'group' => 'Мастер-классы', 'route' => 'outreach.index', 'sort' => 70],

            ['key' => 'shop.index', 'title' => 'Магазин работ', 'group' => 'Магазин', 'route' => 'shop.index', 'sort' => 80],
            ['key' => 'courses.index', 'title' => 'Онлайн-курсы', 'group' => 'Курсы', 'route' => 'courses.index', 'sort' => 90],
            ['key' => 'reviews.index', 'title' => 'Отзывы', 'group' => 'Отзывы', 'route' => 'reviews.index', 'sort' => 100],
            ['key' => 'guides.index', 'title' => 'Материалы', 'group' => 'Материалы', 'route' => 'guides.index', 'sort' => 110],
            ['key' => 'search', 'title' => 'Поиск', 'group' => 'Основное', 'route' => 'search', 'sort' => 120],
        ];
    }

    /** @return array<string, string> имя маршрута => ключ статической страницы */
    public static function routeKeyMap(): array
    {
        $map = [];

        foreach (self::registry() as $page) {
            $map[$page['route']] = $page['key'];
        }

        return $map;
    }

    /** Приводит наличие статических страниц в реестре к актуальному списку. */
    public static function syncStatic(): void
    {
        foreach (self::registry() as $page) {
            SitePage::query()->updateOrCreate(
                ['key' => $page['key']],
                [
                    'source_type' => null,
                    'source_id' => null,
                    'title' => $page['title'],
                    'group' => $page['group'],
                    'route_name' => $page['route'],
                    'route_params' => null,
                    'sort' => $page['sort'],
                ],
            );
        }
    }

    public static function syncContentPage(ContentPage $page): SitePage
    {
        return SitePage::query()->updateOrCreate(
            ['source_type' => $page->getMorphClass(), 'source_id' => $page->getKey()],
            [
                'title' => $page->title,
                'group' => 'Материалы',
                'route_name' => 'guides.show',
                'route_params' => ['page' => $page->slug],
                'sort' => 10000 + (int) $page->sort,
            ],
        );
    }

    public static function syncLegalDocument(LegalDocument $document): SitePage
    {
        return SitePage::query()->updateOrCreate(
            ['source_type' => $document->getMorphClass(), 'source_id' => $document->getKey()],
            [
                'title' => $document->title,
                'group' => 'Правовые документы',
                'route_name' => 'legal.show',
                'route_params' => ['document' => $document->slug],
                'sort' => 20000 + (int) $document->sort,
            ],
        );
    }

    /** Полная синхронизация реестра: статика, материалы и правовые документы. */
    public static function syncAll(): void
    {
        self::syncStatic();

        ContentPage::query()->each(fn (ContentPage $page) => self::syncContentPage($page));
        LegalDocument::query()->each(fn (LegalDocument $document) => self::syncLegalDocument($document));
    }

    public static function removeFor(Model $source): void
    {
        SitePage::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->delete();
    }

    /** Страница реестра для текущего запроса или null, если страница не отслеживается. */
    public static function findForRequest(Request $request): ?SitePage
    {
        $route = $request->route();

        if ($route === null) {
            return null;
        }

        $name = $route->getName();

        if ($name === 'guides.show') {
            $contentPage = $route->parameter('page');

            return $contentPage instanceof ContentPage
                ? self::findBySource($contentPage)
                : null;
        }

        if ($name === 'legal.show') {
            $document = $route->parameter('document');

            return $document instanceof LegalDocument
                ? self::findBySource($document)
                : null;
        }

        $key = self::routeKeyMap()[$name] ?? null;

        return $key !== null
            ? SitePage::query()->where('key', $key)->first()
            : null;
    }

    private static function findBySource(Model $source): ?SitePage
    {
        return SitePage::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->first();
    }
}
