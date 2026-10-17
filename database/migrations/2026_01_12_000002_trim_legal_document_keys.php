<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Технический ключ правового документа используется в коде как имя документа
 * (legal('privacy_policy'), ссылки в футере и на формах). Пробелы по краям
 * ломали поиск по ключу: документ, созданный в админке как «privacy_policy »,
 * не находился ни в футере, ни в шаблонах. Обрезаем пробелы у существующих
 * записей и сбрасываем кэш репозитория LegalDocuments.
 */
return new class extends Migration
{
    public function up(): void
    {
        $used = [];

        foreach (DB::table('legal_documents')->orderBy('id')->get(['id', 'key']) as $row) {
            $key = trim((string) $row->key);

            if ($key === '') {
                $key = 'document-'.$row->id;
            }

            $base = $key;
            $suffix = 2;

            while (in_array($key, $used, true)) {
                $key = $base.'-'.$suffix++;
            }

            $used[] = $key;

            if ($key !== $row->key) {
                DB::table('legal_documents')->where('id', $row->id)->update(['key' => $key]);
            }
        }

        Cache::forget('app.legal_documents.all');
    }

    public function down(): void
    {
        // Обрезка пробелов необратима: исходные значения восстановить нельзя.
    }
};
