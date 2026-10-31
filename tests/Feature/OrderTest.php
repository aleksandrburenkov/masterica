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
use Illuminate\Support\Facades\Hash;
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
            'accept_offer' => '1',
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

    public function test_checkout_validation_errors_render_as_fixed_notification(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->from(route('checkout.index'))
            ->followingRedirects()
            ->post(route('checkout.store'), []);

        $response->assertOk()
            ->assertSee('flash-stack', false)
            ->assertSee('data-flash-toast', false)
            ->assertSee('Проверьте заполнение формы', false);
    }

    public function test_checkout_ajax_validation_errors_return_json(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->postJson(route('checkout.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contact_name', 'contact_phone', 'contact_email']);
    }

    public function test_checkout_ajax_success_returns_redirect_url(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->postJson(route('checkout.store'), $this->contact());

        $response->assertCreated()
            ->assertJsonStructure(['message', 'redirect']);

        $order = Order::firstOrFail();
        $this->assertSame(route('checkout.success', $order->number), $response->json('redirect'));
    }

    public function test_registration_from_checkout_returns_to_checkout_with_cart(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();

        $this->withSession([
            'cart' => [$variant->id => 2],
            'url.intended' => route('checkout.index'),
        ])->post(route('register.store'), [
            'name' => 'Новый Клиент',
            'email' => 'checkout-new@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'consent_data' => '1',
        ])->assertRedirect(route('checkout.index'));

        $this->assertAuthenticated();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee($variant->product->title);
    }

    public function test_login_from_checkout_returns_to_checkout_with_cart(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'checkout-login@example.com',
            'password' => Hash::make('secret-password'),
            'role' => UserRole::Client,
        ]);

        $this->withSession([
            'cart' => [$variant->id => 3],
            'url.intended' => route('checkout.index'),
        ])->post(route('login.store'), [
            'login' => 'checkout-login@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('checkout.index'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee($variant->product->title);
    }

    public function test_checkout_with_empty_cart_redirects_with_info_without_error(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), $this->contact())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('info');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_ajax_with_empty_cart_returns_redirect_without_error(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->postJson(route('checkout.store'), $this->contact())
            ->assertOk()
            ->assertJsonPath('redirect', route('cart.index'))
            ->assertJsonMissingValidationErrors(['cart']);

        $this->assertSame(0, Order::count());
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

    public function test_cart_add_returns_json_for_ajax(): void
    {
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();

        $this->postJson(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertCreated()
            ->assertJsonStructure(['message']);
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
