<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Поле «Порядок» допускает отрицательные значения (например, чтобы поставить
 * запись в начало списка), но все колонки sort были UNSIGNED. В MySQL строгого
 * режима вставка отрицательного числа падала с ошибкой 1264. Переводим колонки
 * в знаковый INT, сохраняя значение по умолчанию 0.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'techniques',
        'masters',
        'masterclasses',
        'products',
        'courses',
        'course_modules',
        'lessons',
        'content_pages',
        'legal_documents',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sort')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->integer('sort')->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sort')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedInteger('sort')->default(0)->change();
            });
        }
    }
};
