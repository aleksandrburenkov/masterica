<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Интервалы километража и наценка на участника для выездных мастер-классов.
 * Интервалы редактируются в админке (раздел «Тарифы удалённости»).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distance_tariffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('min_distance');
            $table->unsignedSmallInteger('max_distance')->nullable();
            $table->unsignedInteger('surcharge_per_participant')->default(0);
            $table->timestamps();

            $table->index(['min_distance', 'max_distance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distance_tariffs');
    }
};
