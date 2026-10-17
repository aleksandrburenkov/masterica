<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Правовые документы платформы «Корни» (политики, согласия, оферта,
 * лицензия, пользовательское соглашение). Документы хранятся в базе и
 * редактируются владельцем через админ-панель, а не хардкодятся в шаблонах.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            // Технический ключ для обращения из кода и шаблонов, например privacy_policy.
            $table->string('key')->unique();
            // Человекочитаемый слаг для публичного URL (st. 152-ФЗ и др.).
            $table->string('slug')->unique();
            $table->string('title');
            // Краткая аннотация: используется в футере и в подписях чекбоксов согласий.
            $table->text('lead')->nullable();
            // Полный текст документа (HTML) для страницы /dokumenty/{slug}.
            $table->longText('body')->nullable();
            // Редакция и дата вступления в силу — для корректной идентификации версии.
            $table->string('version', 32)->nullable();
            $table->date('effective_date')->nullable();
            // Согласно ст. 9 152-ФЗ согласие оформляется до начала обработки:
            // флаг подтверждает, что текст готов к использованию в production.
            $table->boolean('is_production_ready')->default(false)->index();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
