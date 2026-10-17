<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StockService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
    }

    private function contact(): array
    {
        return [
            'contact_name' => 'Взрослый',
            'contact_phone' => '+7 900 111-22-33',
            'contact_email' => 'buyer@example.com',
            'delivery_method' => 'pickup',
            'consent_data' => '1',
        ];
    }

    public function test_placing_order_reserves_stock_and_snapshots_price(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $stock = app(StockService::class);
        $availableBefore = $stock->availableFor($variant);

        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->post(route('checkout.store'), $this->contact())
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame('unpaid', $order->payment_status->value);
        $this->assertCount(1, $order->items);

        $item = $order->items->first();
        $this->assertSame($variant->id, $item->variant_id);
        $this->assertSame($variant->price_cents, $item->unit_price_cents);
        $this->assertSame($variant->sku, $item->sku);
        $this->assertSame('RUB', $item->currency);

        // Physical stock is unchanged; availability drops because of the reservation.
        $this->assertSame($variant->stock, $variant->fresh()->stock);
        $this->assertSame($availableBefore - 1, $stock->availableFor($variant->fresh()));
    }

    public function test_order_cannot_exceed_available_stock(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 5]])
            ->post(route('checkout.store'), $this->contact())
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, Order::count());
    }

    public function test_cancelling_order_releases_stock(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $stock = app(StockService::class);
        $availableBefore = $stock->availableFor($variant);
        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->post(route('checkout.store'), $this->contact());

        $order = Order::firstOrFail();
        $this->assertSame($availableBefore - 1, $stock->availableFor($variant->fresh()));

        $this->actingAs($user)->post(route('account.order.cancel', $order->number))->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame($availableBefore, $stock->availableFor($variant->fresh()));
    }

    public function test_cart_add_validates_variant(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertRedirect();

        $this->post(route('cart.add'), ['variant_id' => 999999, 'quantity' => 1])
            ->assertSessionHasErrors('variant_id');
    }

    public function test_completing_order_twice_does_not_double_write_off(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);
        $service = app(OrderService::class);

        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->post(route('checkout.store'), $this->contact());

        $order = Order::firstOrFail();
        $stockAfterReserve = $variant->fresh()->stock;

        $service->complete($order);
        $stockAfterComplete = $variant->fresh()->stock;
        $this->assertSame($stockAfterReserve - 1, $stockAfterComplete);

        $service->complete($order->fresh());
        $this->assertSame($stockAfterComplete, $variant->fresh()->stock);
    }

    public function test_marking_cancelled_order_paid_is_a_noop(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);
        $service = app(OrderService::class);

        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->post(route('checkout.store'), $this->contact());

        $order = Order::firstOrFail();
        $service->cancel($order, 'Отмена', $user);

        $service->markPaid($order->fresh(), $user);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame('unpaid', $order->payment_status->value);
    }
}
