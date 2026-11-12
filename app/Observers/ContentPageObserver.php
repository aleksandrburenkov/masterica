<?php

namespace App\Observers;

use App\Models\ContentPage;
use App\Support\SitePages;

/**
 * Поддерживает реестр страниц в актуальном состоянии: при создании и
 * сохранении материала появляется/обновляется строка в site_pages, при
 * удалении — удаляется. Благодаря этому новая страница сразу видна в разделе
 * «Настройки» → «Страницы сайта».
 */
class ContentPageObserver
{
    public function saved(ContentPage $page): void
    {
        SitePages::syncContentPage($page);
    }

    public function deleted(ContentPage $page): void
    {
        SitePages::removeFor($page);
    }
}
