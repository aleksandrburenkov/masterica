<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Признак активности страницы перенесён в общий реестр site_pages, поэтому
 * отдельная колонка у материалов больше не нужна.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('content_pages', 'is_active')) {
            return;
        }

        Schema::table('content_pages', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('content_pages', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('is_published');
        });
    }
};
