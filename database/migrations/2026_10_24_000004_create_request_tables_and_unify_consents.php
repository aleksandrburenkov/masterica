<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Форма контактов хранится отдельно от заявок, а записи на мастер-классы
 * остаются в bookings. Здесь же фиксируются согласия на обработку данных и
 * рассылку, чтобы все формы хранили их одинаково.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name');
            $table->string('contact_phone', 32);
            $table->string('contact_email');
            $table->text('message');
            $table->string('status', 32)->default('new')->index();
            $table->boolean('consent_data')->default(false);
            $table->boolean('consent_marketing')->default(false);
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::table('outreach_requests', function (Blueprint $table) {
            $table->boolean('consent_data')->default(false);
            $table->boolean('consent_marketing')->default(false);
            $table->string('source')->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('consent_data')->default(false);
            $table->boolean('consent_marketing')->default(false);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('consent_data')->default(false);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('consent_marketing')->default(false);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('consent_marketing')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('consent_marketing');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('consent_marketing');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('consent_data');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['consent_data', 'consent_marketing']);
        });

        Schema::table('outreach_requests', function (Blueprint $table) {
            $table->dropColumn(['consent_data', 'consent_marketing', 'source']);
        });

        Schema::dropIfExists('contact_requests');
    }
};
