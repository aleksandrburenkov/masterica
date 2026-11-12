<?php

use App\Models\ContentPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Переносит уже созданные материалы (content_pages) в единый реестр страниц,
 * сохраняя прежний признак активности. Новые страницы добавляются в реестр
 * автоматически наблюдателем ContentPageObserver.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('content_pages')->eachById(function (object $page) use ($now): void {
            $exists = DB::table('site_pages')
                ->where('source_type', ContentPage::class)
                ->where('source_id', $page->id)
                ->exists();

            if ($exists) {
                return;
            }

            DB::table('site_pages')->insert([
                'source_type' => ContentPage::class,
                'source_id' => $page->id,
                'key' => null,
                'title' => $page->title,
                'group' => 'Материалы',
                'route_name' => 'guides.show',
                'route_params' => json_encode(['page' => $page->slug], JSON_UNESCAPED_UNICODE),
                'is_active' => $page->is_active ?? true,
                'sort' => 10000 + (int) $page->sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        Cache::forget('app.settings.all');
    }

    public function down(): void
    {
        DB::table('site_pages')->where('source_type', ContentPage::class)->delete();
    }
};
