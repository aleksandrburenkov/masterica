<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masterclasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technique_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('description')->nullable();
            $table->text('outcome')->nullable();
            $table->string('level', 32)->default('beginner');
            $table->unsignedInteger('age_min')->default(6);
            $table->unsignedInteger('age_max')->nullable();
            $table->unsignedInteger('duration_minutes')->default(120);
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency', 3)->default('RUB');
            $table->boolean('materials_included')->default(true);
            $table->string('format', 32)->default('studio');
            $table->string('image')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('workshop_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masterclass_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->string('timezone', 64)->default('Europe/Moscow');
            $table->unsignedInteger('capacity')->default(8);
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('location_name')->nullable();
            $table->string('address')->nullable();
            $table->string('status', 32)->default('scheduled');
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['starts_at', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_sessions');
        Schema::dropIfExists('masterclasses');
    }
};
