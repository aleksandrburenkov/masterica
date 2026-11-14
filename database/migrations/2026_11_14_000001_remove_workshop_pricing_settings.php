<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $keys = [
        'pricing.workshop_price_cents',
        'pricing.workshop_duration_minutes',
        'pricing.workshop_capacity',
    ];

    /** @var array<string, array{0:string,1:string}> */
    private array $defaults = [
        'pricing.workshop_price_cents' => ['150000', 'int'],
        'pricing.workshop_duration_minutes' => ['120', 'int'],
        'pricing.workshop_capacity' => ['8', 'int'],
    ];

    public function up(): void
    {
        DB::table('settings')->whereIn('key', $this->keys)->delete();

        Cache::forget('app.settings.all');
    }

    public function down(): void
    {
        $now = now();

        foreach ($this->defaults as $key => [$value, $type]) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'group' => 'pricing',
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cache::forget('app.settings.all');
    }
};
