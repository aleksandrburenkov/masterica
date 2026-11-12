<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Текст страницы «на техническом обслуживании», который видят посетители
 * неактивных страниц. Редактируется в админке в разделе
 * «Настройки» → «Страницы сайта». Существующее значение не перезаписывается.
 */
return new class extends Migration
{
    /** @var array<string, array{0:string,1:string}> */
    private array $settings = [
        'pages.maintenance_text' => ['Страница находится на техническом обслуживании.', 'text'],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->settings as $key => [$value, $type]) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'group' => 'pages',
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cache::forget('app.settings.all');
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys($this->settings))->delete();

        Cache::forget('app.settings.all');
    }
};
