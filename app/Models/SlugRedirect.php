<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * История изменений слагов: старый адрес → актуальный.
 *
 * Используется для 301-редиректов, чтобы смена URL не приводила к битым
 * ссылкам и не теряла накопленную ссылочную массу. Записи хранятся в
 * «плоском» виде: при переименовании A → B → C старый адрес A сразу
 * указывает на C.
 */
#[Fillable(['model_type', 'old_slug', 'new_slug'])]
class SlugRedirect extends Model
{
    public static function record(string $modelType, string $oldSlug, string $newSlug): void
    {
        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        DB::transaction(function () use ($modelType, $oldSlug, $newSlug): void {
            // Выравниваем цепочку: всё, что вело на старый слаг, теперь ведёт на новый.
            static::query()
                ->where('model_type', $modelType)
                ->where('new_slug', $oldSlug)
                ->where('old_slug', '!=', $newSlug)
                ->update(['new_slug' => $newSlug, 'updated_at' => now()]);

            static::query()
                ->where('model_type', $modelType)
                ->where('new_slug', $oldSlug)
                ->where('old_slug', $newSlug)
                ->delete();

            // Живой адрес всегда важнее исторического: старый алиас на новый слаг удаляем.
            static::query()
                ->where('model_type', $modelType)
                ->where('old_slug', $newSlug)
                ->delete();

            static::query()->updateOrCreate(
                ['model_type' => $modelType, 'old_slug' => $oldSlug],
                ['new_slug' => $newSlug],
            );
        });
    }

    /**
     * Запись заняла слаг: исторические алиасы на это значение больше не нужны,
     * иначе они могли бы перехватывать живой адрес после следующих переименований.
     */
    public static function forget(string $modelType, string $slug): void
    {
        if ($slug === '') {
            return;
        }

        static::query()
            ->where('model_type', $modelType)
            ->where(fn ($query) => $query->where('old_slug', $slug)->orWhere('new_slug', $slug))
            ->delete();
    }
}
