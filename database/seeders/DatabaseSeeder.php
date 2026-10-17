<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Production-safe content: studio settings, legal documents, catalogue and course.
        $this->call([
            SettingsSeeder::class,
            LegalDocumentsSeeder::class,
            CatalogSeeder::class,
        ]);

        // Local-only demonstration data (synthetic profiles and reviews).
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
