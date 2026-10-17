<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('masterclass_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16)->default('text');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('body');
            $table->string('video_path')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->boolean('consent_publish')->default(false);
            $table->boolean('consent_marketing')->default(false);
            $table->string('source')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 32)->default('guide')->index();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('reason')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('app');
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('content_pages');
        Schema::dropIfExists('reviews');
    }
};
