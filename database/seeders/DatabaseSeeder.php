<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Production-safe content: studio settings and catalogue.
        // Правовые документы создаются вручную через админ-панель.
        $this->call([
            SettingsSeeder::class,
            CatalogSeeder::class,
        ]);

        // Local-only demonstration data (synthetic profiles and reviews).
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }

        // Единый реестр страниц сайта: статические маршруты, материалы и
        // правовые документы. Выполняется последним, чтобы собрать все страницы.
        $this->call(SitePagesSeeder::class);
    }
}
