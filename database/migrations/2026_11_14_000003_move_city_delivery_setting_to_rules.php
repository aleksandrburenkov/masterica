<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $oldKey = 'pricing.delivery_city_cents';

    private string $newKey = 'rules.delivery_city_cents';

    public function up(): void
    {
        $this->move($this->oldKey, $this->newKey, 'rules');
    }

    public function down(): void
    {
        $this->move($this->newKey, $this->oldKey, 'pricing');
    }

    private function move(string $from, string $to, string $group): void
    {
        if (! DB::table('settings')->where('key', $from)->exists()) {
            return;
        }

        if (DB::table('settings')->where('key', $to)->exists()) {
            DB::table('settings')->where('key', $from)->delete();
        } else {
            DB::table('settings')->where('key', $from)->update([
                'key' => $to,
                'group' => $group,
                'updated_at' => now(),
            ]);
        }

        Cache::forget('app.settings.all');
    }
};
