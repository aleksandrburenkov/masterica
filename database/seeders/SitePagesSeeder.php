<?php

namespace Database\Seeders;

use App\Support\SitePages;
use Illuminate\Database\Seeder;

/**
 * Наполняет единый реестр страниц сайта: статические маршруты, материалы и
 * правовые документы. Благодаря этому раздел «Настройки» → «Страницы сайта»
 * содержит все страницы сайта независимо от того, как они созданы.
 */
class SitePagesSeeder extends Seeder
{
    public function run(): void
    {
        SitePages::syncAll();
    }
}
