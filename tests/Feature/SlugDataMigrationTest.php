<?php

namespace Tests\Feature;

use App\Models\Masterclass;
use App\Models\Product;
use App\Models\SlugRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlugDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_slugs_are_normalized_and_duplicates_are_renamed(): void
    {
        DB::table('products')->insert([
            ['title' => 'Первый товар', 'slug' => 'Test Product', 'short_description' => 'A', 'price_cents' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Второй товар', 'slug' => 'test-product', 'short_description' => 'B', 'price_cents' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Ваза', 'slug' => '!!!', 'short_description' => 'C', 'price_cents' => 100, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('masterclasses')->insert([
            'title' => 'Мастер-класс',
            'slug' => '  Мастер Класс  ',
            'short_description' => 'D',
            'price_cents' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_25_000003_normalize_existing_slugs.php');
        $migration->up();

        $slugs = DB::table('products')->orderBy('id')->pluck('slug')->all();

        $this->assertSame(['test-product', 'test-product-2', 'vaza'], $slugs);
        $this->assertSame('master-klass', DB::table('masterclasses')->value('slug'));

        $this->assertDatabaseHas('slug_redirects', [
            'model_type' => Product::class,
            'old_slug' => 'test-product',
            'new_slug' => 'test-product-2',
        ]);

        $this->assertDatabaseHas('slug_redirects', [
            'model_type' => Product::class,
            'old_slug' => '!!!',
            'new_slug' => 'vaza',
        ]);

        $this->assertDatabaseHas('slug_redirects', [
            'model_type' => Masterclass::class,
            'old_slug' => '  Мастер Класс  ',
            'new_slug' => 'master-klass',
        ]);

        foreach ([...$slugs, 'master-klass'] as $slug) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug);
        }

        $this->assertSame(3, SlugRedirect::query()->where('model_type', Product::class)->count());
    }

    public function test_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_10_25_000003_normalize_existing_slugs.php');

        $migration->up();
        $before = DB::table('products')->orderBy('id')->pluck('slug')->all();

        $migration->up();

        $this->assertSame($before, DB::table('products')->orderBy('id')->pluck('slug')->all());
        $this->assertSame(0, SlugRedirect::query()->count());
    }
}
