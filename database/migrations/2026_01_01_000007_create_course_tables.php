<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technique_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('description')->nullable();
            $table->text('outcome')->nullable();
            $table->string('level', 32)->default('beginner');
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency', 3)->default('RUB');
            $table->unsignedInteger('access_days')->default(365);
            $table->string('image')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable();
            $table->string('video_path')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['course_module_id', 'slug']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('pending_payment')->index();
            $table->string('payment_status', 32)->default('unpaid');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['course_enrollment_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
    }
};
