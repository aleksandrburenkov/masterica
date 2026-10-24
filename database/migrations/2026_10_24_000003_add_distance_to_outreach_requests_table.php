<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сохраняем рассчитанную удалённость и наценку в заявке, чтобы менеджер видел,
 * из чего сложилась предварительная стоимость.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outreach_requests', function (Blueprint $table) {
            $table->decimal('distance_km', 6, 1)->nullable()->after('location');
            $table->unsignedInteger('distance_surcharge_cents')->default(0)->after('extra_price_cents');
        });
    }

    public function down(): void
    {
        Schema::table('outreach_requests', function (Blueprint $table) {
            $table->dropColumn(['distance_km', 'distance_surcharge_cents']);
        });
    }
};
