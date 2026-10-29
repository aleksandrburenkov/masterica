<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('extension', 16)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedBigInteger('processed_size_bytes')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('poster_path')->nullable();

            $table->string('status', 32)->default('pending')->index();
            $table->string('scan_status', 32)->default('pending')->index();
            $table->string('scanner', 32)->nullable();
            $table->text('scan_report')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scan_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_videos');
    }
};
