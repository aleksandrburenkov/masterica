<?php

use App\Models\ContentPage;
use App\Models\Course;
use App\Models\LegalDocument;
use App\Models\Lesson;
use App\Models\Masterclass;
use App\Models\Product;
use App\Models\SlugRedirect;
use App\Models\Technique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Приводит уже сохранённые слаги к правилам HasSlug: латиница, нижний регистр,
 * только буквы/цифры/дефисы, ограничение длины и уникальность. Для публичных
 * сущностей старые адреса сохраняются в slug_redirects и отдают 301.
 */
return new class extends Migration
{
    /** @return list<array{model: class-string, source: string, max: int, scope: ?string}> */
    private function targets(): array
    {
        return [
            ['model' => Technique::class, 'source' => 'name', 'max' => 120, 'scope' => null],
            ['model' => Masterclass::class, 'source' => 'title', 'max' => 180, 'scope' => null],
            ['model' => Product::class, 'source' => 'title', 'max' => 180, 'scope' => null],
            ['model' => Course::class, 'source' => 'title', 'max' => 180, 'scope' => null],
            ['model' => Lesson::class, 'source' => 'title', 'max' => 120, 'scope' => 'course_module_id'],
            ['model' => ContentPage::class, 'source' => 'title', 'max' => 180, 'scope' => null],
            ['model' => LegalDocument::class, 'source' => 'title', 'max' => 120, 'scope' => null],
        ];
    }

    public function up(): void
    {
        foreach ($this->targets() as $target) {
            $this->normalize($target);
        }
    }

    public function down(): void
    {
        // Нормализация необратима: прежние значения сохранены в slug_redirects.
    }

    /** @param array{model: class-string, source: string, max: int, scope: ?string} $target */
    private function normalize(array $target): void
    {
        $model = $target['model'];
        $table = (new $model)->getTable();
        $scopeColumn = $target['scope'];

        $columns = ['id', 'slug', $target['source']];

        if ($scopeColumn !== null) {
            $columns[] = $scopeColumn;
        }

        $records = DB::table($table)->orderBy('id')->get($columns);

        $used = [];
        $changes = [];

        foreach ($records as $record) {
            $base = $this->normalizeValue((string) $record->slug, $target['max']);

            if ($base === '') {
                $base = $this->normalizeValue((string) ($record->{$target['source']} ?? ''), $target['max']);
            }

            if ($base === '') {
                $base = 'zapis';
            }

            $scopeValue = $scopeColumn !== null ? (string) ($record->{$scopeColumn} ?? '') : '';
            $key = $scopeValue.'|';

            $candidate = $base;
            $suffix = 2;

            while (isset($used[$key.$candidate])) {
                $tail = '-'.$suffix++;
                $candidate = trim(Str::limit($base, max(1, $target['max'] - strlen($tail)), ''), '-').$tail;
            }

            $used[$key.$candidate] = true;

            if ($candidate !== (string) $record->slug) {
                $changes[] = ['id' => $record->id, 'old' => (string) $record->slug, 'new' => $candidate];
            }
        }

        if ($changes === []) {
            return;
        }

        $isPublic = method_exists($model, 'slugRouteName') && $model::slugRouteName() !== null;

        DB::transaction(function () use ($table, $changes, $isPublic, $model): void {
            // Сначала временные значения: они исключают конфликт уникального индекса
            // при обмене слагами между записями.
            foreach ($changes as $change) {
                DB::table($table)->where('id', $change['id'])->update(['slug' => '__tmp-slug-'.$change['id']]);
            }

            foreach ($changes as $change) {
                DB::table($table)->where('id', $change['id'])->update(['slug' => $change['new']]);
            }

            if (! $isPublic) {
                return;
            }

            foreach ($changes as $change) {
                SlugRedirect::record($model, $change['old'], $change['new']);
            }
        });
    }

    private function normalizeValue(string $value, int $max): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/')) {
            $value = Str::afterLast($value, '/');
        }

        $slug = trim(Str::slug($value, '-'), '-');

        if ($slug === '') {
            return '';
        }

        return trim(Str::limit($slug, $max, ''), '-');
    }
};
