<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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

class StockSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
    }

    private function variant(): ProductVariant
    {
        return ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();
    }

    private function available(ProductVariant $variant): int
    {
        return app(StockService::class)->availableFor($variant);
    }

    private function client(): User
    {
        return User::factory()->create(['role' => UserRole::Client]);
    }

    private function placeOrder(User $user, ProductVariant $variant, int $quantity = 1): Order
    {
        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => $quantity]])
            ->post(route('checkout.store'), [
                'contact_name' => 'Взрослый',
                'contact_phone' => '+7 900 111-22-33',
                'contact_email' => 'buyer@example.com',
                'delivery_method' => 'pickup',
                'accept_offer' => '1',
                'consent_data' => '1',
            ])
            ->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_placing_order_reduces_availability_on_shop_card(): void
    {
        $variant = $this->variant();
        $this->placeOrder($this->client(), $variant);

        $this->assertSame($variant->stock - 1, $this->available($variant));

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('В наличии: '.($variant->stock - 1), false);

        $this->get(route('shop.show', 'basket-paper-vine'))
            ->assertOk()
            ->assertSee('В наличии: '.($variant->stock - 1), false);
    }

    public function test_completing_order_writes_off_stock_but_availability_is_unchanged(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);

        app(OrderService::class)->complete($order, $order->user);

        $this->assertSame($variant->stock - 1, $variant->fresh()->stock);
        $this->assertSame($variant->stock - 1, $this->available($variant->fresh()));
    }

    public function test_cancelling_live_order_restores_availability(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);

        app(OrderService::class)->cancel($order, 'Отмена', $order->user);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame($variant->stock, $variant->fresh()->stock);
        $this->assertSame($variant->stock, $this->available($variant->fresh()));
    }

    public function test_refunding_live_order_restores_availability(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);

        app(OrderService::class)->refund($order, 'Возврат', $order->user);

        $order->refresh();
        $this->assertSame(OrderStatus::Refunded, $order->status);
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
        $this->assertSame($variant->stock, $this->available($variant->fresh()));
    }

    public function test_refunding_completed_order_returns_stock(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);

        $service = app(OrderService::class);
        $service->complete($order, $order->user);
        $this->assertSame($variant->stock - 1, $variant->fresh()->stock);

        $service->refund($order->fresh(), 'Возврат', $order->user);

        $this->assertSame($variant->stock, $variant->fresh()->stock);
        $this->assertSame($variant->stock, $this->available($variant->fresh()));
    }

    public function test_cancelling_completed_order_returns_stock_only_once(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);

        $service = app(OrderService::class);
        $service->complete($order, $order->user);
        $this->assertSame($variant->stock - 1, $variant->fresh()->stock);

        $service->cancel($order->fresh(), 'Отмена выполненного', $order->user);
        $this->assertSame($variant->stock, $variant->fresh()->stock);

        // Повторная отмена не должна вернуть остаток ещё раз.
        $service->cancel($order->fresh(), 'Повторная отмена', $order->user);
        $this->assertSame($variant->stock, $variant->fresh()->stock);
    }

    public function test_deleting_live_order_releases_reservation(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);
        $this->assertSame($variant->stock - 1, $this->available($variant));

        $order->delete();

        $this->assertSame($variant->stock, $variant->fresh()->stock);
        $this->assertSame($variant->stock, $this->available($variant->fresh()));
    }

    public function test_deleting_completed_order_returns_stock(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);
        app(OrderService::class)->complete($order, $order->user);
        $this->assertSame($variant->stock - 1, $variant->fresh()->stock);

        $order->fresh()->delete();

        $this->assertSame($variant->stock, $variant->fresh()->stock);
        $this->assertSame($variant->stock, $this->available($variant->fresh()));
    }

    public function test_admin_status_change_to_cancelled_returns_stock(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);
        app(OrderService::class)->complete($order, $order->user);
        $this->assertSame($variant->stock - 1, $variant->fresh()->stock);

        // Прямая смена статуса (как из формы админки) также пересчитывает остаток.
        $order->fresh()->forceFill(['status' => OrderStatus::Cancelled])->save();

        $this->assertSame($variant->stock, $variant->fresh()->stock);
        $this->assertSame($variant->stock, $this->available($variant->fresh()));
    }

    public function test_user_can_delete_own_order_and_stock_is_restored(): void
    {
        $variant = $this->variant();
        $user = $this->client();
        $order = $this->placeOrder($user, $variant);

        $this->actingAs($user)
            ->delete(route('account.order.delete', $order->number))
            ->assertRedirect(route('account.orders'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertSame($variant->stock, $this->available($variant));
    }

    public function test_user_cannot_delete_another_users_order(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);

        $this->actingAs($this->client())
            ->delete(route('account.order.delete', $order->number))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_own_completed_order(): void
    {
        $variant = $this->variant();
        $user = $this->client();
        $order = $this->placeOrder($user, $variant);
        app(OrderService::class)->complete($order, $user);

        $this->actingAs($user)
            ->delete(route('account.order.delete', $order->number))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertSame($variant->stock - 1, $variant->fresh()->stock);
    }

    public function test_availability_never_double_returns_after_delete(): void
    {
        $variant = $this->variant();
        $order = $this->placeOrder($this->client(), $variant);

        $service = app(OrderService::class);
        $service->complete($order, $order->user);
        $service->cancel($order->fresh(), 'Отмена', $order->user);
        $this->assertSame($variant->stock, $variant->fresh()->stock);

        // Удаление уже возвращённого заказа не должно снова увеличить остаток.
        $order->fresh()->delete();
        $this->assertSame($variant->stock, $variant->fresh()->stock);
    }
}
