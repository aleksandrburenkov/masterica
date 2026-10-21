<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masterclasses', function (Blueprint $table) {
            $table->unsignedInteger('outreach_price_cents')->nullable();
            $table->unsignedInteger('included_participants')->default(10);
            $table->json('participant_surcharges')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('masterclasses', function (Blueprint $table) {
            $table->dropColumn(['outreach_price_cents', 'included_participants', 'participant_surcharges']);
        });
    }
};
