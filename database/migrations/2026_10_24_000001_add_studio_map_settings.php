<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Координаты и масштаб карты на странице «Контакты». Редактируются в админке
 * в разделе «Настройки студии» → «Студия и контакты». Существующие значения
 * не перезаписываются.
 */
return new class extends Migration
{
    /** @var array<string, array{0:string,1:string}> */
    private array $settings = [
        'studio.latitude' => ['53.216894', 'string'],
        'studio.longitude' => ['34.348181', 'string'],
        'studio.map_zoom' => ['17', 'int'],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->settings as $key => [$value, $type]) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'group' => 'studio',
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
