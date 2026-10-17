<?php

namespace Tests\Feature;

use App\Models\ProductVariant;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_catalog_keeps_stock_consistent_with_ledger(): void
    {
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);

        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $initialStock = $variant->stock;
        $movementCount = $variant->movements()->count();

        // Re-running the seeder must not reset the cached stock or duplicate
        // the opening-balance movement.
        $this->seed(CatalogSeeder::class);

        $variant->refresh();
        $this->assertSame($initialStock, $variant->stock);
        $this->assertSame($movementCount, $variant->movements()->count());
    }
}
