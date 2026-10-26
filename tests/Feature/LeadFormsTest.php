<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Filament\Resources\ContactRequests\Pages\CreateContactRequest;
use App\Filament\Resources\Masterclasses\MasterclassResource;
use App\Filament\Resources\OutreachRequests\OutreachRequestResource;
use App\Models\Booking;
use App\Models\ContactRequest;
use App\Models\Masterclass;
use App\Models\OutreachRequest;
use App\Models\User;
use App\Models\WorkshopSession;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
    }

    private function makeSession(?Masterclass $masterclass = null, int $capacity = 8): WorkshopSession
    {
        $masterclass ??= Masterclass::query()->firstOrFail();

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

    private function makeBooking(WorkshopSession $session, array $attributes = []): Booking
    {
        return Booking::create(array_merge([
            'number' => 'BK-'.uniqid(),
            'workshop_session_id' => $session->id,
            'status' => BookingStatus::Held,
            'payment_status' => PaymentStatus::Unpaid,
            'participants_count' => 1,
            'contact_name' => 'Тест',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'booking-'.uniqid().'@example.com',
            'unit_price_cents' => 100,
            'total_cents' => 100,
        ], $attributes));
    }

    public function test_footer_form_creates_booking_not_outreach_request(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.quick-store'), [
            'workshop_session_id' => $session->id,
            'participants_count' => 2,
            'contact_name' => 'Организатор',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'lead@example.com',
            'child_age' => 9,
            'notes' => 'Хотим занятие на праздник',
            'consent_data' => '1',
            'consent_marketing' => '1',
            'source' => '/o-nas',
        ])->assertRedirect(url('/o-nas'));

        $booking = Booking::firstOrFail();

        $this->assertSame($session->id, $booking->workshop_session_id);
        $this->assertSame(2, $booking->participants_count);
        $this->assertSame('lead@example.com', $booking->contact_email);
        $this->assertSame('Хотим занятие на праздник', $booking->notes);
        $this->assertTrue($booking->consent_data);
        $this->assertTrue($booking->consent_marketing);
        $this->assertSame($session->priceCents() * 2, $booking->total_cents);
        $this->assertSame(0, OutreachRequest::count());
    }

    public function test_footer_form_requires_data_consent(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.quick-store'), [
            'workshop_session_id' => $session->id,
            'participants_count' => 1,
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'guest@example.com',
        ])->assertSessionHasErrors('consent_data');

        $this->assertSame(0, Booking::count());
    }

    public function test_footer_form_returns_json_for_ajax(): void
    {
        $session = $this->makeSession();

        $this->postJson(route('bookings.quick-store'), [
            'workshop_session_id' => $session->id,
            'participants_count' => 1,
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'guest@example.com',
            'consent_data' => '1',
        ])->assertCreated()->assertJsonStructure(['message', 'number']);
    }

    public function test_footer_form_rejects_session_without_free_seats(): void
    {
        $session = $this->makeSession(capacity: 1);

        Booking::create([
            'number' => 'BK-OCCUPIED-1',
            'workshop_session_id' => $session->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'participants_count' => 1,
            'contact_name' => 'Занято',
            'contact_phone' => '1',
            'contact_email' => 'busy@example.com',
            'unit_price_cents' => 100,
            'total_cents' => 100,
        ]);

        $this->postJson(route('bookings.quick-store'), [
            'workshop_session_id' => $session->id,
            'participants_count' => 1,
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'guest@example.com',
            'consent_data' => '1',
        ])->assertStatus(422)->assertJsonValidationErrors('workshop_session_id');

        $this->assertSame(1, Booking::count());
    }

    public function test_contacts_form_creates_contact_request_with_all_fields(): void
    {
        $this->post(route('contacts.store'), [
            'contact_name' => 'Ирина',
            'contact_phone' => '+7 906 698-31-06',
            'contact_email' => 'irina@example.com',
            'message' => 'Можно ли прийти с ребёнком 5 лет?',
            'consent_data' => '1',
            'consent_marketing' => '1',
            'source' => 'kontakty',
        ])->assertRedirect(route('contacts'));

        $request = ContactRequest::firstOrFail();

        $this->assertSame('Ирина', $request->contact_name);
        $this->assertSame('+7 906 698-31-06', $request->contact_phone);
        $this->assertSame('irina@example.com', $request->contact_email);
        $this->assertSame('Можно ли прийти с ребёнком 5 лет?', $request->message);
        $this->assertTrue($request->consent_data);
        $this->assertTrue($request->consent_marketing);
        $this->assertSame('kontakty', $request->source);
        $this->assertSame(0, OutreachRequest::count());
    }

    public function test_contacts_form_requires_data_consent_and_message(): void
    {
        $this->post(route('contacts.store'), [
            'contact_name' => 'Ирина',
            'contact_phone' => '+7 906 698-31-06',
            'contact_email' => 'irina@example.com',
            'message' => '',
        ])->assertSessionHasErrors(['message', 'consent_data']);

        $this->assertSame(0, ContactRequest::count());
    }

    public function test_masterclass_page_booking_stores_marketing_consent_without_duplicates(): void
    {
        $session = $this->makeSession();

        $this->post(route('bookings.store', $session->masterclass->slug), [
            'workshop_session_id' => $session->id,
            'participants_count' => 2,
            'contact_name' => 'Взрослый',
            'contact_phone' => '+7 900 111-22-33',
            'contact_email' => 'adult@example.com',
            'child_age' => 9,
            'notes' => 'Нужен фартук',
            'consent_data' => '1',
            'consent_marketing' => '1',
        ])->assertRedirect(route('masterclasses.show', $session->masterclass->slug));

        $booking = Booking::firstOrFail();

        $this->assertTrue($booking->consent_data);
        $this->assertTrue($booking->consent_marketing);
        $this->assertSame(1, Booking::count());
        $this->assertSame(0, OutreachRequest::count());
    }

    public function test_outreach_request_stores_consents_and_source(): void
    {
        $this->post(route('outreach.store'), [
            'direction' => 'basket',
            'requested_date' => now()->addWeek()->toDateString(),
            'location' => 'г. Брянск',
            'participants_count' => 8,
            'contact_name' => 'Школа',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'school@example.com',
            'consent_data' => '1',
            'consent_marketing' => '1',
            'source' => 'vyezdnye-master-klassy',
        ])->assertRedirect(route('outreach.index'));

        $request = OutreachRequest::firstOrFail();

        $this->assertTrue($request->consent_data);
        $this->assertTrue($request->consent_marketing);
        $this->assertSame('vyezdnye-master-klassy', $request->source);
    }

    public function test_masterclass_without_sessions_shows_request_input_on_index(): void
    {
        WorkshopSession::query()->delete();
        $masterclass = Masterclass::query()->where('slug', 'basket')->firstOrFail();

        $this->get(route('masterclasses.index', ['request' => $masterclass->slug]))
            ->assertOk()
            ->assertSee('name="request_masterclass"', false)
            ->assertSee('Запрос-Корзина из бумажной лозы')
            ->assertSee(route('bookings.request-store'), false);
    }

    public function test_index_ignores_request_mode_when_masterclass_has_sessions(): void
    {
        WorkshopSession::query()->delete();
        $masterclass = Masterclass::query()->where('slug', 'basket')->firstOrFail();
        $this->makeSession($masterclass);

        $this->get(route('masterclasses.index', ['request' => $masterclass->slug]))
            ->assertOk()
            ->assertDontSee('name="request_masterclass"', false);
    }

    public function test_index_ignores_request_mode_for_unknown_slug(): void
    {
        $this->get(route('masterclasses.index', ['request' => 'no-such-masterclass']))
            ->assertOk()
            ->assertDontSee('name="request_masterclass"', false);
    }

    public function test_request_store_creates_contact_request_with_server_side_title(): void
    {
        WorkshopSession::query()->delete();
        $masterclass = Masterclass::query()->where('slug', 'basket')->firstOrFail();

        $this->postJson(route('bookings.request-store'), [
            'request_masterclass' => $masterclass->slug,
            'request_title' => 'Запрос-подмена <script>alert(1)</script>',
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'request@example.com',
            'consent_data' => '1',
            'source' => 'master-klassy',
        ])
            ->assertCreated()
            ->assertJson(['message' => 'Ваш запрос принят и обрабатывается, ответ будет отправлен по Email']);

        $request = ContactRequest::firstOrFail();

        $this->assertSame('Запрос-Корзина из бумажной лозы', $request->message);
        $this->assertSame('request@example.com', $request->contact_email);
        $this->assertSame('master-klassy', $request->source);
        $this->assertSame(1, ContactRequest::count());
        $this->assertSame(0, Booking::count());
    }

    public function test_request_store_appends_comment_to_message(): void
    {
        WorkshopSession::query()->delete();

        $this->postJson(route('bookings.request-store'), [
            'request_masterclass' => 'basket',
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'request@example.com',
            'notes' => 'Интересует суббота',
            'consent_data' => '1',
        ])->assertCreated();

        $this->assertSame(
            "Запрос-Корзина из бумажной лозы\n\nКомментарий: Интересует суббота",
            ContactRequest::firstOrFail()->message,
        );
    }

    public function test_request_label_strips_control_characters_and_blank_titles(): void
    {
        $injected = new Masterclass(['title' => "Корзина\r\nBcc: evil@example.com"]);
        $this->assertSame('Запрос-Корзина Bcc: evil@example.com', $injected->requestLabel());

        $blank = new Masterclass(['title' => '   ']);
        $this->assertSame('Запрос', $blank->requestLabel());
    }

    public function test_request_store_redirects_with_success_message(): void
    {
        WorkshopSession::query()->delete();

        $this->post(route('bookings.request-store'), [
            'request_masterclass' => 'basket',
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'request@example.com',
            'consent_data' => '1',
            'source' => 'master-klassy',
        ])
            ->assertRedirect(url('master-klassy'))
            ->assertSessionHas('success', 'Ваш запрос принят и обрабатывается, ответ будет отправлен по Email');

        $this->assertSame(1, ContactRequest::count());
    }

    public function test_request_store_rejects_masterclass_with_active_sessions(): void
    {
        WorkshopSession::query()->delete();
        $masterclass = Masterclass::query()->where('slug', 'basket')->firstOrFail();
        $this->makeSession($masterclass);

        $this->postJson(route('bookings.request-store'), [
            'request_masterclass' => $masterclass->slug,
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'request@example.com',
            'consent_data' => '1',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('request_masterclass');

        $this->assertSame(0, ContactRequest::count());
    }

    public function test_request_store_rejects_unknown_slug(): void
    {
        $this->postJson(route('bookings.request-store'), [
            'request_masterclass' => 'no-such-masterclass',
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'request@example.com',
            'consent_data' => '1',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('request_masterclass');

        $this->assertSame(0, ContactRequest::count());
    }

    public function test_request_store_requires_data_consent(): void
    {
        WorkshopSession::query()->delete();

        $this->postJson(route('bookings.request-store'), [
            'request_masterclass' => 'basket',
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'request@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('consent_data');

        $this->assertSame(0, ContactRequest::count());
    }

    public function test_lead_forms_are_visible_in_their_admin_sections(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);
        $session = $this->makeSession();

        $booking = Booking::create([
            'number' => 'BK-TEST-0001',
            'workshop_session_id' => $session->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'participants_count' => 2,
            'contact_name' => 'Тест',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'booking-lead@example.com',
            'unit_price_cents' => 100,
            'total_cents' => 200,
        ]);

        ContactRequest::create([
            'number' => 'CT-TEST-0001',
            'contact_name' => 'Тест',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'contact-lead@example.com',
            'message' => 'Проверка формы контактов',
            'consent_data' => true,
        ]);

        $this->actingAs($owner)
            ->get('/admin/bookings')
            ->assertOk()
            ->assertSee($booking->number);

        $this->actingAs($owner)
            ->get('/admin/contact-requests')
            ->assertOk()
            ->assertSee('Проверка формы контактов');
    }

    public function test_admin_navigation_groups_and_labels(): void
    {
        $this->assertSame('Заявки', BookingResource::getNavigationGroup());
        $this->assertSame('Заявки', OutreachRequestResource::getNavigationGroup());
        $this->assertSame('Мастер-классы', BookingResource::getNavigationLabel());
        $this->assertSame('Создание мастер-класса', MasterclassResource::getNavigationLabel());
        $this->assertSame('Выездные мастер-классы', OutreachRequestResource::getNavigationLabel());
        $this->assertSame('Форма контакты', ContactRequestResource::getNavigationLabel());
        $this->assertNull(ContactRequestResource::getNavigationGroup());
    }

    public function test_admin_can_create_contact_request(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->actingAs($owner);

        Livewire::test(CreateContactRequest::class)
            ->fillForm([
                'contact_name' => 'Менеджер',
                'contact_phone' => '+7 900 000-00-00',
                'contact_email' => 'manual-contact@example.com',
                'message' => 'Ручное обращение',
                'status' => 'new',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertStringStartsWith('CT-', ContactRequest::firstOrFail()->number);
    }

    public function test_booking_badge_counts_unprocessed_bookings(): void
    {
        $session = $this->makeSession();

        $this->assertNull(BookingResource::getNavigationBadge());

        $this->makeBooking($session, ['number' => 'BK-NEW', 'status' => BookingStatus::New]);
        $this->makeBooking($session, ['number' => 'BK-HELD', 'status' => BookingStatus::Held]);
        $this->makeBooking($session, ['number' => 'BK-PENDING', 'status' => BookingStatus::PendingPayment]);
        $this->makeBooking($session, ['number' => 'BK-CONFIRMED', 'status' => BookingStatus::Confirmed]);

        $this->assertSame('3', BookingResource::getNavigationBadge());
    }

    public function test_booking_badge_disappears_when_booking_is_processed(): void
    {
        $session = $this->makeSession();
        $booking = $this->makeBooking($session, ['status' => BookingStatus::Held]);

        $this->assertSame('1', BookingResource::getNavigationBadge());

        $booking->update(['status' => BookingStatus::Confirmed]);

        $this->assertNull(BookingResource::getNavigationBadge());
    }

    public function test_saving_a_booking_status_change_dispatches_sidebar_refresh(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->actingAs($owner);

        $session = $this->makeSession();
        $booking = $this->makeBooking($session, ['status' => BookingStatus::Held]);

        $this->assertSame('1', BookingResource::getNavigationBadge());

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->fillForm(['status' => BookingStatus::Confirmed->value])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertDispatched('refresh-sidebar');

        $this->assertNull(BookingResource::getNavigationBadge());
    }
}
