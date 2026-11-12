<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Единый реестр страниц сайта. Строка описывает одну публичную страницу:
 *  - статические страницы маршрутов (home, about, contacts…) задаются ключом;
 *  - динамические страницы (материалы, правовые документы) ссылаются на
 *    исходную модель через полиморфную связь source.
 *
 * Признак is_active управляет показом страницы: неактивная страница отдаёт
 * посетителю страницу «на техническом обслуживании». Настройка — в админке,
 * раздел «Настройки» → «Страницы сайта».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('source');
            $table->string('key')->nullable()->unique();
            $table->string('title');
            $table->string('group')->default('Прочее');
            $table->string('route_name');
            $table->json('route_params')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }
};
