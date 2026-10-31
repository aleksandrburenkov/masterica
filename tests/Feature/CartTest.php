<?php

namespace Tests\Feature;

use App\Models\ProductVariant;
use App\Services\StockService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Без демонстрационного заказа: наличие равно физическому остатку.
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
    }

    private function available(ProductVariant $variant): int
    {
        return app(StockService::class)->availableFor($variant);
    }

    private function variant(): ProductVariant
    {
        return ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
    }

    public function test_header_renders_hidden_badge_and_empty_state_without_items(): void
    {
        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('data-cart', false)
            ->assertSee('data-cart-count hidden', false)
            ->assertSee('cart-panel__empty', false);
    }

    public function test_header_renders_badge_and_lines_for_existing_items(): void
    {
        $variant = $this->variant();

        $this->withSession(['cart' => [$variant->id => 2]])
            ->get(route('shop.index'))
            ->assertOk()
            ->assertSee('data-cart', false)
            ->assertSee('cart-widget__count', false)
            ->assertSee('data-cart-variant="'.$variant->id.'"', false)
            ->assertSee('data-cart-subtotal', false);
    }

    public function test_state_endpoint_returns_totals_for_session_cart(): void
    {
        $variant = $this->variant();

        $this->withSession(['cart' => [$variant->id => 2]])
            ->getJson(route('cart.state'))
            ->assertOk()
            ->assertJsonPath('cart.count', 2)
            ->assertJsonPath('cart.subtotal_cents', $variant->price_cents * 2)
            ->assertJsonCount(1, 'cart.items')
            ->assertJsonPath('cart.items.0.variant_id', $variant->id)
            ->assertJsonPath('cart.items.0.quantity', 2);
    }

    public function test_state_endpoint_is_empty_by_default(): void
    {
        $this->getJson(route('cart.state'))
            ->assertOk()
            ->assertJsonPath('cart.count', 0)
            ->assertJsonPath('cart.subtotal_cents', 0)
            ->assertJsonCount(0, 'cart.items');
    }

    public function test_add_returns_fresh_snapshot_for_ajax(): void
    {
        $variant = $this->variant();

        $this->postJson(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertCreated()
            ->assertJsonStructure(['message', 'cart' => ['count', 'subtotal_cents', 'subtotal', 'items']])
            ->assertJsonPath('cart.count', 1);
    }

    public function test_repeated_add_increments_quantity_within_stock(): void
    {
        $variant = $this->variant();
        $limit = $this->available($variant);

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        }

        $this->getJson(route('cart.state'))
            ->assertOk()
            ->assertJsonPath('cart.count', $limit)
            ->assertJsonCount(1, 'cart.items');
    }

    public function test_repeated_add_cannot_exceed_stock(): void
    {
        $variant = $this->variant();
        $limit = $this->available($variant);

        $this->postJson(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertCreated();
        $this->postJson(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => $limit + 5])
            ->assertCreated()
            ->assertJsonPath('cart.count', $limit)
            ->assertJsonPath('cart.items.0.max', $limit);
    }

    public function test_state_lists_multiple_distinct_items_and_sums_totals(): void
    {
        $first = $this->variant();
        $second = ProductVariant::where('sku', 'KOR-ORG-01')->firstOrFail();

        $this->withSession(['cart' => [$first->id => 1, $second->id => 2]])
            ->getJson(route('cart.state'))
            ->assertOk()
            ->assertJsonPath('cart.count', 3)
            ->assertJsonCount(2, 'cart.items')
            ->assertJsonPath(
                'cart.subtotal_cents',
                $first->price_cents + $second->price_cents * 2,
            );
    }

    public function test_update_returns_snapshot_and_recalculates_totals(): void
    {
        $variant = $this->variant();

        $this->withSession(['cart' => [$variant->id => 1]])
            ->patchJson(route('cart.update', $variant), ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cart.count', 2)
            ->assertJsonPath('cart.subtotal_cents', $variant->price_cents * 2)
            ->assertJsonPath('cart.items.0.max', 2);
    }

    public function test_update_cannot_exceed_stock(): void
    {
        $variant = $this->variant();
        $limit = $this->available($variant);

        $this->withSession(['cart' => [$variant->id => 1]])
            ->patchJson(route('cart.update', $variant), ['quantity' => $limit + 5])
            ->assertOk()
            ->assertJsonPath('cart.count', $limit)
            ->assertJsonPath('cart.items.0.quantity', $limit);
    }

    public function test_update_to_zero_removes_the_item(): void
    {
        $variant = $this->variant();

        $this->withSession(['cart' => [$variant->id => 2]])
            ->patchJson(route('cart.update', $variant), ['quantity' => 0])
            ->assertOk()
            ->assertJsonPath('cart.count', 0)
            ->assertJsonCount(0, 'cart.items');
    }

    public function test_remove_deletes_the_item(): void
    {
        $variant = $this->variant();

        $this->withSession(['cart' => [$variant->id => 1]])
            ->deleteJson(route('cart.remove', $variant))
            ->assertOk()
            ->assertJsonPath('cart.count', 0)
            ->assertJsonCount(0, 'cart.items');
    }

    public function test_cart_page_renders_stock_limited_quantity_control(): void
    {
        $variant = $this->variant();
        $limit = $this->available($variant);

        $this->withSession(['cart' => [$variant->id => $limit]])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('data-cart-item', false)
            ->assertSee('data-cart-max="'.$limit.'"', false)
            ->assertSee('aria-label="Увеличить количество" disabled', false);

        $this->withSession(['cart' => [$variant->id => 1]])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('aria-label="Уменьшить количество" disabled', false);
    }

    public function test_header_widget_badge_reflects_stock(): void
    {
        $variant = $this->variant();
        $limit = $this->available($variant);

        $this->withSession(['cart' => [$variant->id => $limit]])
            ->get(route('shop.index'))
            ->assertOk()
            ->assertSee('data-cart-max="'.$limit.'"', false);
    }

    public function test_non_ajax_add_still_redirects_back(): void
    {
        $variant = $this->variant();

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
