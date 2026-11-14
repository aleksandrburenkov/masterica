<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('group', 'pricing')->delete();

        Cache::forget('app.settings.all');
    }

    public function down(): void {}
};
