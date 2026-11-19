<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\StudioSettings;
use App\Filament\Resources\NotificationRecipients\Pages\CreateNotificationRecipient;
use App\Filament\Resources\NotificationRecipients\Pages\ListNotificationRecipients;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Mail\NewRequestNotificationMail;
use App\Models\Booking;
use App\Models\ContactRequest;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Masterclass;
use App\Models\NotificationRecipient;
use App\Models\Order;
use App\Models\OutreachRequest;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WorkshopSession;
use App\Services\RequestNotificationRecipientResolver;
use App\Support\Settings;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RequestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
        Mail::fake();
    }

    private function owner(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::Owner,
            'email' => 'owner@example.com',
            'name' => 'Владелец студии',
        ], $attributes));
    }

    private function manager(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::Manager,
            'email' => 'manager@example.com',
            'name' => 'Менеджер студии',
        ], $attributes));
    }

    private function makeSession(?Masterclass $masterclass = null): WorkshopSession
    {
        $masterclass ??= Masterclass::query()->firstOrFail();

        return WorkshopSession::create([
            'masterclass_id' => $masterclass->id,
            'starts_at' => now()->addDays(5)->setTime(15, 0),
            'timezone' => 'Europe/Moscow',
            'capacity' => 8,
            'location_name' => 'Студия',
            'address' => 'Брянск',
            'status' => 'scheduled',
            'is_published' => true,
        ]);
    }

    private function fieldValue(NewRequestNotificationMail $mail, string $label): ?string
    {
        foreach ($mail->requestData->fields as $field) {
            if ($field['label'] === $label) {
                return $field['value'];
            }
        }

        return null;
    }

    private function submitContact(): ContactRequest
    {
        $this->post(route('contacts.store'), [
            'contact_name' => 'Ирина',
            'contact_phone' => '+7 906 698-31-06',
            'contact_email' => 'irina@example.com',
            'message' => 'Можно ли прийти с ребёнком 5 лет?',
            'consent_data' => '1',
            'source' => 'kontakty',
        ])->assertRedirect(route('contacts'));

        return ContactRequest::firstOrFail();
    }

    public function test_contact_request_sends_html_email_to_owner_with_admin_link(): void
    {
        $this->owner();
        $request = $this->submitContact();

        Mail::assertSent(NewRequestNotificationMail::class, function (NewRequestNotificationMail $mail) use ($request): bool {
            $html = $mail->render();

            return $mail->hasTo('owner@example.com')
                && $mail->requestData->number === $request->number
                && $mail->requestData->type === 'Обращение с сайта'
                && str_contains($mail->requestData->adminUrl, '/admin/contact-requests/'.$request->id.'/edit')
                && str_contains($html, e($request->contact_name))
                && str_contains($html, e($request->contact_email))
                && str_contains($html, e($request->message))
                && str_contains($html, e($mail->requestData->adminUrl));
        });
    }

    public function test_booking_request_sends_email_to_owner(): void
    {
        $this->owner();
        $session = $this->makeSession();

        $this->post(route('bookings.quick-store'), [
            'workshop_session_id' => $session->id,
            'participants_count' => 2,
            'contact_name' => 'Организатор',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'lead@example.com',
            'consent_data' => '1',
        ])->assertRedirect(route('home'));

        Mail::assertSent(NewRequestNotificationMail::class, function (NewRequestNotificationMail $mail): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->requestData->type === 'Запись на мастер-класс'
                && str_contains($mail->requestData->adminUrl, '/admin/bookings/');
        });
    }

    public function test_outreach_request_sends_email_to_owner(): void
    {
        $this->owner();

        $this->post(route('outreach.store'), [
            'direction' => 'basket',
            'requested_date' => now()->addWeek()->toDateString(),
            'location' => 'г. Брянск',
            'participants_count' => 8,
            'contact_name' => 'Школа',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'school@example.com',
            'consent_data' => '1',
        ])->assertRedirect(route('outreach.index'));

        $request = OutreachRequest::firstOrFail();

        Mail::assertSent(NewRequestNotificationMail::class, function (NewRequestNotificationMail $mail) use ($request): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->requestData->type === 'Заявка на выездной мастер-класс'
                && $mail->requestData->number === $request->number
                && str_contains($mail->requestData->adminUrl, '/admin/outreach-requests/'.$request->id.'/edit');
        });
    }

    public function test_masterclass_request_without_sessions_is_sent_as_contact_request(): void
    {
        $this->owner();
        WorkshopSession::query()->delete();

        $this->postJson(route('bookings.request-store'), [
            'request_masterclass' => 'basket',
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'request@example.com',
            'consent_data' => '1',
            'source' => 'master-klassy',
        ])->assertCreated();

        $request = ContactRequest::firstOrFail();

        Mail::assertSent(NewRequestNotificationMail::class, function (NewRequestNotificationMail $mail) use ($request): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->requestData->type === 'Запрос мастер-класса'
                && str_contains($mail->requestData->adminUrl, '/admin/contact-requests/'.$request->id.'/edit')
                && $this->fieldValue($mail, 'Источник') === 'master-klassy';
        });
    }

    public function test_footer_quick_booking_sends_email_with_source(): void
    {
        $this->owner();
        $session = $this->makeSession();

        $this->post(route('bookings.quick-store'), [
            'workshop_session_id' => $session->id,
            'participants_count' => 1,
            'contact_name' => 'Гость',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'footer@example.com',
            'consent_data' => '1',
            'source' => '/o-studii',
        ])->assertRedirect(url('/o-studii'));

        $booking = Booking::firstOrFail();

        $this->assertSame('/o-studii', $booking->source);

        Mail::assertSent(NewRequestNotificationMail::class, function (NewRequestNotificationMail $mail) use ($booking): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->requestData->type === 'Запись на мастер-класс'
                && $mail->requestData->number === $booking->number
                && $this->fieldValue($mail, 'Источник') === '/o-studii'
                && str_contains($mail->requestData->adminUrl, '/admin/bookings/'.$booking->id.'/edit');
        });
    }

    public function test_new_course_enrollment_sends_email_to_owner(): void
    {
        $this->owner();
        $user = User::factory()->create([
            'role' => UserRole::Client,
            'name' => 'Ученик',
            'email' => 'student@example.com',
        ]);
        $course = Course::where('slug', 'paper-vine-basket-course')->firstOrFail();

        $this->actingAs($user)
            ->post(route('courses.enroll', $course->slug))
            ->assertRedirect(route('account.courses'));

        $enrollment = CourseEnrollment::firstOrFail();

        Mail::assertSent(NewRequestNotificationMail::class, function (NewRequestNotificationMail $mail) use ($enrollment, $course): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->requestData->type === 'Заявка на онлайн-курс'
                && $mail->requestData->number === 'EN-'.str_pad((string) $enrollment->id, 6, '0', STR_PAD_LEFT)
                && $this->fieldValue($mail, 'Курс') === $course->title
                && $this->fieldValue($mail, 'Email пользователя') === 'student@example.com'
                && str_contains($mail->requestData->adminUrl, '/admin/course-enrollments/'.$enrollment->id.'/edit');
        });
    }

    public function test_repeated_course_enrollment_does_not_send_duplicate_email(): void
    {
        $this->owner();
        $user = User::factory()->create(['role' => UserRole::Client]);
        $course = Course::where('slug', 'paper-vine-basket-course')->firstOrFail();

        $this->actingAs($user)->post(route('courses.enroll', $course->slug));
        $this->actingAs($user)->post(route('courses.enroll', $course->slug));

        $this->assertSame(1, CourseEnrollment::count());
        Mail::assertSentOnce(NewRequestNotificationMail::class);
    }

    public function test_new_shop_order_sends_email_to_owner(): void
    {
        $this->owner();
        $user = User::factory()->create(['role' => UserRole::Client]);
        $variant = ProductVariant::where('sku', 'KOR-BASKET-01')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 2]])
            ->post(route('checkout.store'), [
                'contact_name' => 'Покупатель',
                'contact_phone' => '+7 900 111-22-33',
                'contact_email' => 'buyer@example.com',
                'delivery_method' => 'pickup',
                'accept_offer' => '1',
                'consent_data' => '1',
            ])
            ->assertRedirect();

        $order = Order::firstOrFail();

        Mail::assertSent(NewRequestNotificationMail::class, function (NewRequestNotificationMail $mail) use ($order, $variant): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->requestData->type === 'Заказ из магазина'
                && $mail->requestData->number === $order->number
                && str_contains($mail->requestData->adminUrl, '/admin/orders/'.$order->id.'/edit')
                && str_contains($this->fieldValue($mail, 'Состав заказа'), $variant->product->title)
                && $this->fieldValue($mail, 'Итого') !== '—';
        });
    }

    public function test_valid_alternate_owner_email_takes_priority_over_owner_profile_email(): void
    {
        $this->owner();
        Settings::set(RequestNotificationRecipientResolver::ALTERNATE_EMAIL_SETTING, 'alt-owner@example.com');

        $this->submitContact();

        Mail::assertSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('alt-owner@example.com'));
        Mail::assertNotSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('owner@example.com'));
    }

    public function test_invalid_alternate_owner_email_falls_back_to_owner_profile_email(): void
    {
        $this->owner();
        Settings::set(RequestNotificationRecipientResolver::ALTERNATE_EMAIL_SETTING, 'not-an-email');

        $this->submitContact();

        Mail::assertSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('owner@example.com'));
        Mail::assertNotSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('not-an-email'));
    }

    public function test_empty_alternate_owner_email_uses_owner_profile_email(): void
    {
        $this->owner();

        $this->submitContact();

        Mail::assertSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('owner@example.com'));
    }

    public function test_mailing_list_recipients_receive_email_in_addition_to_owner(): void
    {
        $this->owner();
        $manager = $this->manager();
        NotificationRecipient::create(['user_id' => $manager->id]);

        $this->submitContact();

        Mail::assertSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('owner@example.com'));
        Mail::assertSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('manager@example.com'));
        Mail::assertSentTimes(NewRequestNotificationMail::class, 2);
    }

    public function test_duplicate_recipient_addresses_are_sent_once(): void
    {
        $this->owner();
        $manager = $this->manager(['email' => 'alt-owner@example.com']);
        NotificationRecipient::create(['user_id' => $manager->id]);
        Settings::set(RequestNotificationRecipientResolver::ALTERNATE_EMAIL_SETTING, 'alt-owner@example.com');

        $this->submitContact();

        Mail::assertSentOnce(NewRequestNotificationMail::class);
    }

    public function test_invalid_recipient_email_is_skipped(): void
    {
        $this->owner(['email' => 'owner@example.com']);
        $manager = $this->manager(['email' => 'broken-email']);
        NotificationRecipient::create(['user_id' => $manager->id]);

        $this->submitContact();

        Mail::assertSentOnce(NewRequestNotificationMail::class);
        Mail::assertNotSent(NewRequestNotificationMail::class, fn (NewRequestNotificationMail $mail): bool => $mail->hasTo('broken-email'));
    }

    public function test_no_email_is_sent_without_owner_and_subscribers(): void
    {
        $this->submitContact();

        Mail::assertNothingSent();
    }

    public function test_only_owner_can_manage_mailing_list(): void
    {
        $owner = $this->owner();
        $manager = $this->manager();
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($owner)->get('/admin/notification-recipients')->assertOk();
        $this->actingAs($manager)->get('/admin/notification-recipients')->assertForbidden();
        $this->actingAs($client)->get('/admin/notification-recipients')->assertForbidden();
    }

    public function test_owner_can_add_and_delete_recipient_from_mailing_list(): void
    {
        $owner = $this->owner();
        $manager = $this->manager();
        $this->actingAs($owner);

        Livewire::test(CreateNotificationRecipient::class)
            ->fillForm(['user_id' => $manager->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('notification_recipients', ['user_id' => $manager->id]);

        $recipient = NotificationRecipient::query()->where('user_id', $manager->id)->firstOrFail();

        Livewire::test(ListNotificationRecipients::class)
            ->callTableAction('delete', $recipient);

        $this->assertDatabaseMissing('notification_recipients', ['user_id' => $manager->id]);
    }

    public function test_recipient_cannot_be_added_twice(): void
    {
        $owner = $this->owner();
        $manager = $this->manager();
        NotificationRecipient::create(['user_id' => $manager->id]);
        $this->actingAs($owner);

        Livewire::test(CreateNotificationRecipient::class)
            ->fillForm(['user_id' => $manager->id])
            ->call('create')
            ->assertHasFormErrors(['user_id']);

        $this->assertSame(1, NotificationRecipient::count());
    }

    public function test_user_with_invalid_email_cannot_be_added_to_mailing_list(): void
    {
        $owner = $this->owner();
        $manager = $this->manager(['email' => 'broken-email']);
        $this->actingAs($owner);

        Livewire::test(CreateNotificationRecipient::class)
            ->fillForm(['user_id' => $manager->id])
            ->call('create')
            ->assertHasFormErrors(['user_id']);

        $this->assertSame(0, NotificationRecipient::count());
    }

    public function test_alternate_owner_email_is_validated_in_settings(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner);

        Livewire::test(StudioSettings::class)
            ->fillForm(['alternate_owner_email' => 'not-an-email'])
            ->call('save')
            ->assertHasFormErrors(['alternate_owner_email']);

        $this->assertSame(
            '',
            (string) Settings::get(RequestNotificationRecipientResolver::ALTERNATE_EMAIL_SETTING),
        );

        Livewire::test(StudioSettings::class)
            ->fillForm(['alternate_owner_email' => 'alt-owner@example.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'alt-owner@example.com',
            Settings::get(RequestNotificationRecipientResolver::ALTERNATE_EMAIL_SETTING),
        );
    }

    public function test_owner_created_user_can_be_subscribed_via_user_form(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Новый менеджер',
                'email' => 'new-manager@example.com',
                'role' => UserRole::Manager->value,
                'password' => 'password',
                'receives_request_notifications' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', 'new-manager@example.com')->firstOrFail();

        $this->assertDatabaseHas('notification_recipients', ['user_id' => $created->id]);

        Livewire::test(EditUser::class, ['record' => $created->getRouteKey()])
            ->fillForm(['receives_request_notifications' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('notification_recipients', ['user_id' => $created->id]);
    }

    public function test_success_response_for_user_is_unchanged_when_notification_is_sent(): void
    {
        $this->owner();

        $this->post(route('contacts.store'), [
            'contact_name' => 'Ирина',
            'contact_phone' => '+7 906 698-31-06',
            'contact_email' => 'irina@example.com',
            'message' => 'Вопрос',
            'consent_data' => '1',
        ])
            ->assertRedirect(route('contacts'))
            ->assertSessionHas('success');

        Mail::assertSent(NewRequestNotificationMail::class);
    }
}
