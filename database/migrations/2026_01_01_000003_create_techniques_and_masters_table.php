<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('techniques', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('specialization')->nullable();
            $table->string('photo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('master_technique', function (Blueprint $table) {
            $table->foreignId('master_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technique_id')->constrained()->cascadeOnDelete();
            $table->primary(['master_id', 'technique_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_technique');
        Schema::dropIfExists('masters');
        Schema::dropIfExists('techniques');
    }
};
