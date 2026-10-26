<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Masterclass;
use App\Models\User;
use App\Models\WorkshopSession;
use App\Services\BookingService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
    }

    private function makeSession(int $capacity = 8): WorkshopSession
    {
        $masterclass = Masterclass::query()->firstOrFail();

        return WorkshopSession::create([
            'masterclass_id' => $masterclass->id,
            'starts_at' => now()->addDays(5)->setTime(15, 0),
            'timezone' => 'Europe/Moscow',
            'capacity' => $capacity,
            'location_name' => 'Студия',
            'address' => 'Брянск',
            'status' => 'scheduled',
            'is_published' => true,
        ]);
    }

    private function payload(WorkshopSession $session, int $participants = 1): array
    {
        return [
            'workshop_session_id' => $session->id,
            'participants_count' => $participants,
            'contact_name' => 'Взрослый',
            'contact_phone' => '+7 900 111-22-33',
            'contact_email' => 'adult@example.com',
            'consent_data' => '1',
        ];
    }

    public function test_guest_sees_editable_booking_form(): void
    {
        $this->get(route('masterclasses.show', 'basket'))
            ->assertOk()
            ->assertSee('Запись на занятие')
            ->assertSee('Регистрация не нужна')
            ->assertSee('name="contact_name"', false)
            ->assertSee('placeholder="Как к вам обращаться"', false)
            ->assertSee('placeholder="+7 (___) ___-__-__"', false)
            ->assertSee('data-ajax-form', false)
            ->assertDontSee('Войти и записаться');
    }

    public function test_authenticated_user_sees_prefilled_booking_form(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Client,
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'phone' => '+7 900 000-00-00',
        ]);

        $this->actingAs($user)
            ->get(route('masterclasses.show', 'basket'))
            ->assertOk()
            ->assertSee('value="Иван Петров"', false)
            ->assertSee('value="ivan@example.com"', false);
    }

    public function test_guest_books_without_account(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.store', 'basket'), $this->payload($session, 2))
            ->assertRedirect(route('masterclasses.show', 'basket'))
            ->assertSessionHas('success');

        $booking = Booking::firstOrFail();
        $this->assertNull($booking->user_id);
        $this->assertSame(BookingStatus::New, $booking->status);
        $this->assertSame('Взрослый', $booking->contact_name);
        $this->assertSame('adult@example.com', $booking->contact_email);
        $this->assertSame(2, $booking->participants_count);
        $this->assertSame($session->priceCents() * 2, $booking->total_cents);
    }

    public function test_guest_books_without_page_reload_via_json(): void
    {
        $session = $this->makeSession();

        $this->postJson(route('bookings.store', 'basket'), $this->payload($session, 2))
            ->assertCreated()
            ->assertJsonStructure(['message', 'number']);

        $booking = Booking::firstOrFail();
        $this->assertNull($booking->user_id);
        $this->assertSame(BookingStatus::New, $booking->status);
        $this->assertSame(2, $booking->participants_count);
    }

    public function test_guest_duplicate_booking_is_rejected(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.store', 'basket'), $this->payload($session))
            ->assertSessionHasNoErrors();
        $this->post(route('bookings.store', 'basket'), $this->payload($session))
            ->assertSessionHasErrors('participants_count');

        $this->assertSame(1, Booking::count());
    }

    public function test_guest_booking_requires_contact_fields(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.store', 'basket'), [
            'workshop_session_id' => $session->id,
            'participants_count' => '',
            'contact_name' => '',
            'contact_phone' => '',
            'contact_email' => '',
            'consent_data' => '1',
        ])->assertSessionHasErrors(['participants_count', 'contact_name', 'contact_phone', 'contact_email']);

        $this->assertSame(0, Booking::count());
    }

    public function test_guest_booking_is_visible_in_admin(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.store', 'basket'), $this->payload($session));

        $owner = User::factory()->create(['role' => UserRole::Owner]);

        $this->actingAs($owner)
            ->get('/admin/bookings')
            ->assertOk()
            ->assertSee('adult@example.com');
    }

    public function test_registered_user_name_is_visible_in_admin(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Client,
            'name' => 'Мария Иванова',
            'email' => 'maria@example.com',
        ]);
        $session = $this->makeSession();

        $this->actingAs($user)->post(route('bookings.store', 'basket'), $this->payload($session));

        $owner = User::factory()->create(['role' => UserRole::Owner]);

        $this->actingAs($owner)
            ->get('/admin/bookings')
            ->assertOk()
            ->assertSee('Мария Иванова')
            ->assertSee('maria@example.com');
    }

    public function test_authenticated_user_creates_new_booking(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);
        $session = $this->makeSession();

        $this->actingAs($user)
            ->post(route('bookings.store', 'basket'), $this->payload($session, 2))
            ->assertRedirect(route('masterclasses.show', 'basket'));

        $this->actingAs($user)
            ->get(route('masterclasses.show', 'basket'))
            ->assertOk()
            ->assertSee('flash-stack', false)
            ->assertSee('data-flash-toast', false)
            ->assertSee('подтверждена', false);

        $booking = Booking::firstOrFail();
        $this->assertSame(BookingStatus::New, $booking->status);
        $this->assertSame('unpaid', $booking->payment_status->value);
        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame(2, $booking->participants_count);
        $this->assertSame($session->priceCents() * 2, $booking->total_cents);
    }

    public function test_authenticated_user_books_without_page_reload_via_json(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);
        $session = $this->makeSession();

        $this->actingAs($user)
            ->postJson(route('bookings.store', 'basket'), $this->payload($session, 2))
            ->assertCreated()
            ->assertJsonStructure(['message', 'number']);

        $booking = Booking::firstOrFail();
        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame(BookingStatus::New, $booking->status);
        $this->assertSame(2, $booking->participants_count);
    }

    public function test_booking_validation_errors_are_returned_as_json(): void
    {
        $session = $this->makeSession();

        $this->postJson(route('bookings.store', 'basket'), [
            'workshop_session_id' => $session->id,
            'participants_count' => '',
            'contact_name' => '',
            'contact_phone' => '',
            'contact_email' => '',
            'consent_data' => '1',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['participants_count', 'contact_name', 'contact_phone', 'contact_email']);

        $this->assertSame(0, Booking::count());
    }

    public function test_duplicate_booking_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);
        $session = $this->makeSession();

        $this->actingAs($user)->post(route('bookings.store', 'basket'), $this->payload($session));
        $this->actingAs($user)->post(route('bookings.store', 'basket'), $this->payload($session))
            ->assertSessionHasErrors('participants_count');

        $this->assertSame(1, Booking::count());
    }

    public function test_last_seat_cannot_be_double_booked(): void
    {
        $first = User::factory()->create(['role' => UserRole::Client]);
        $second = User::factory()->create(['role' => UserRole::Client]);
        $session = $this->makeSession(capacity: 1);

        $this->actingAs($first)->post(route('bookings.store', 'basket'), $this->payload($session))
            ->assertRedirect(route('masterclasses.show', 'basket'));

        $this->actingAs($second)->post(route('bookings.store', 'basket'), $this->payload($session))
            ->assertSessionHasErrors('participants_count');

        $this->assertSame(1, Booking::count());
        $this->assertSame(0, $session->fresh()->seatsLeft());
    }

    public function test_user_can_cancel_own_booking(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);
        $session = $this->makeSession();

        $this->actingAs($user)->post(route('bookings.store', 'basket'), $this->payload($session));
        $booking = Booking::firstOrFail();

        $this->actingAs($user)
            ->post(route('bookings.cancel', $booking->number), ['cancel_reason' => 'Планы изменились'])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame('Планы изменились', $booking->cancel_reason);
    }

    public function test_user_can_rebook_after_cancelling(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);
        $session = $this->makeSession();

        $this->actingAs($user)->post(route('bookings.store', 'basket'), $this->payload($session));
        $booking = Booking::firstOrFail();
        $this->actingAs($user)->post(route('bookings.cancel', $booking->number), ['cancel_reason' => 'Передумал']);

        $this->actingAs($user)->post(route('bookings.store', 'basket'), $this->payload($session))
            ->assertRedirect(route('masterclasses.show', 'basket'));

        $this->assertSame(1, Booking::count());
        $this->assertSame(BookingStatus::New, Booking::first()->status);
        $this->assertNull(Booking::first()->canceled_at);
    }

    public function test_expiring_holds_is_idempotent(): void
    {
        $user = User::factory()->create(['role' => UserRole::Client]);
        $session = $this->makeSession();

        $booking = Booking::create([
            'number' => 'BK-TEST-0001',
            'user_id' => $user->id,
            'workshop_session_id' => $session->id,
            'status' => BookingStatus::Held,
            'participants_count' => 1,
            'contact_name' => 'X',
            'contact_phone' => '1',
            'contact_email' => 'x@example.com',
            'unit_price_cents' => 100,
            'total_cents' => 100,
            'hold_expires_at' => now()->subMinute(),
        ]);

        $service = app(BookingService::class);
        $service->expireStaleHolds();
        $service->expireStaleHolds();

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
    }

    public function test_new_booking_is_marked_new_and_counted_as_unread_in_admin(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.store', 'basket'), $this->payload($session))
            ->assertRedirect(route('masterclasses.show', 'basket'));

        $this->assertSame(BookingStatus::New, Booking::firstOrFail()->status);
        $this->assertSame('1', BookingResource::getNavigationBadge());
    }

    public function test_admin_confirmation_clears_the_unread_booking_badge(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.store', 'basket'), $this->payload($session));
        $booking = Booking::firstOrFail();

        $this->assertSame('1', BookingResource::getNavigationBadge());

        app(BookingService::class)->confirm($booking, 'Проверка');

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertNull(BookingResource::getNavigationBadge());
    }
}
