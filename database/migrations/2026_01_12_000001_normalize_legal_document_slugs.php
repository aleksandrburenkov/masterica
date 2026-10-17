<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Приводит уже сохранённые слаги правовых документов к публично-безопасному
 * виду: слаг — это последний сегмент адреса /dokumenty/{slug}, поэтому в нём
 * недопустим «/». Ранее в форме можно было указать полный путь
 * (например, «dokumenty/politics»), из-за чего ломались и админ-маршрут
 * редактирования /{record}/edit, и публичная страница документа.
 */
return new class extends Migration
{
    public function up(): void
    {
        $used = [];

        foreach (DB::table('legal_documents')->orderBy('id')->get(['id', 'slug']) as $row) {
            $slug = Str::slug(Str::afterLast(trim((string) $row->slug), '/'));

            if ($slug === '') {
                $slug = 'document-'.$row->id;
            }

            $base = $slug;
            $suffix = 2;

            while (in_array($slug, $used, true)) {
                $slug = $base.'-'.$suffix++;
            }

            $used[] = $slug;

            if ($slug !== $row->slug) {
                DB::table('legal_documents')->where('id', $row->id)->update(['slug' => $slug]);
            }
        }
    }

    public function down(): void
    {
        // Нормализация слагов необратима: исходное значение восстановить нельзя.
    }
};
