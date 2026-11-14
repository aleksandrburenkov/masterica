<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $key = 'pricing.delivery_pickup_cents';

    public function up(): void
    {
        DB::table('settings')->where('key', $this->key)->delete();

        Cache::forget('app.settings.all');
    }

    public function down(): void
    {
        if (DB::table('settings')->where('key', $this->key)->exists()) {
            return;
        }

        $now = now();

        DB::table('settings')->insert([
            'group' => 'pricing',
            'key' => $this->key,
            'value' => '0',
            'type' => 'int',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Cache::forget('app.settings.all');
    }
};
