<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Masterclass;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkshopSession;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
    }

    private function otherOrder(User $owner): Order
    {
        $order = Order::create([
            'number' => 'OR-ISO-0001',
            'user_id' => $owner->id,
            'status' => OrderStatus::PendingPayment,
            'payment_status' => 'unpaid',
            'delivery_method' => 'pickup',
            'subtotal_cents' => 1000,
            'delivery_cents' => 0,
            'total_cents' => 1000,
            'currency' => 'RUB',
            'contact_name' => 'Owner',
            'contact_phone' => '1',
            'contact_email' => 'owner@example.com',
        ]);

        return $order;
    }

    private function otherBooking(User $owner): Booking
    {
        $masterclass = Masterclass::firstOrFail();
        $session = WorkshopSession::create([
            'masterclass_id' => $masterclass->id,
            'starts_at' => now()->addDays(3)->setTime(12, 0),
            'timezone' => 'Europe/Moscow',
            'capacity' => 5,
            'status' => 'scheduled',
            'is_published' => true,
        ]);

        return Booking::create([
            'number' => 'BK-ISO-0001',
            'user_id' => $owner->id,
            'workshop_session_id' => $session->id,
            'status' => BookingStatus::Confirmed,
            'participants_count' => 1,
            'contact_name' => 'Owner',
            'contact_phone' => '1',
            'contact_email' => 'owner@example.com',
            'unit_price_cents' => 1000,
            'total_cents' => 1000,
        ]);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Client]);
        $intruder = User::factory()->create(['role' => UserRole::Client]);
        $order = $this->otherOrder($owner);

        $this->actingAs($intruder)
            ->get(route('account.order', $order->number))
            ->assertForbidden();
    }

    public function test_user_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Client]);
        $intruder = User::factory()->create(['role' => UserRole::Client]);
        $order = $this->otherOrder($owner);

        $this->actingAs($intruder)
            ->post(route('account.order.cancel', $order->number))
            ->assertForbidden();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_user_cannot_cancel_another_users_booking(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Client]);
        $intruder = User::factory()->create(['role' => UserRole::Client]);
        $booking = $this->otherBooking($owner);

        $this->actingAs($intruder)
            ->post(route('bookings.cancel', $booking->number))
            ->assertForbidden();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_orders_list_only_shows_own_orders(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Client]);
        $viewer = User::factory()->create(['role' => UserRole::Client]);
        $this->otherOrder($owner);

        $this->actingAs($viewer)
            ->get(route('account.orders'))
            ->assertOk()
            ->assertDontSee('OR-ISO-0001');
    }
}
