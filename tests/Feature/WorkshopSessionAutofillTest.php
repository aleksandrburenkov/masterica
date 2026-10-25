<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\WorkshopSessions\Pages\CreateWorkshopSession;
use App\Filament\Resources\WorkshopSessions\Pages\EditWorkshopSession;
use App\Models\Booking;
use App\Models\Masterclass;
use App\Models\User;
use App\Models\WorkshopSession;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WorkshopSessionAutofillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Owner]));
    }

    public function test_selecting_masterclass_fills_capacity_and_price(): void
    {
        $masterclass = $this->makeMasterclass([
            'included_participants' => 12,
            'price_cents' => 150000,
        ]);

        Livewire::test(CreateWorkshopSession::class)
            ->fillForm(['masterclass_id' => $masterclass->id])
            ->assertFormSet([
                'capacity' => 12,
                'price_cents' => '1 500,00',
            ]);
    }

    public function test_session_form_uses_project_styled_calendar(): void
    {
        Livewire::test(CreateWorkshopSession::class)
            ->assertSee('fi-fo-date-time-picker-panel', false)
            ->assertDontSee('type="datetime-local"', false);

        $this->get('/admin/workshop-sessions/create')
            ->assertOk()
            ->assertSee('css/admin-calendar.css', false)
            ->assertSee('js/admin-calendar.js', false)
            ->assertSee('fi-fo-date-time-picker-panel', false);

        // Глобальная настройка распространяется и на обычные DatePicker.
        $this->get('/admin/outreach-requests/create')
            ->assertOk()
            ->assertSee('fi-fo-date-time-picker-panel', false)
            ->assertDontSee('type="date"', false);
    }

    public function test_past_dates_are_disabled_in_session_calendar(): void
    {
        $today = today('Europe/Moscow')->startOfDay();

        $this->get('/admin/workshop-sessions/create')
            ->assertOk()
            ->assertSee('x-ref="minDate"', false)
            ->assertSee('value="'.$today->format('Y-m-d H:i:s').'"', false);
    }

    public function test_past_session_date_cannot_be_saved_on_create(): void
    {
        $masterclass = $this->makeMasterclass(['slug' => 'past-date-session']);

        Livewire::test(CreateWorkshopSession::class)
            ->fillForm([
                'masterclass_id' => $masterclass->id,
                'starts_at' => now('Europe/Moscow')->subDay()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasFormErrors(['starts_at']);
    }

    public function test_existing_past_session_can_still_be_edited(): void
    {
        $masterclass = $this->makeMasterclass(['slug' => 'past-session-edit']);
        $session = WorkshopSession::create([
            'masterclass_id' => $masterclass->id,
            'starts_at' => now('Europe/Moscow')->subMonth()->setTime(12, 0),
            'timezone' => 'Europe/Moscow',
            'capacity' => 8,
            'price_cents' => 100000,
            'status' => 'scheduled',
            'is_published' => true,
        ]);

        Livewire::test(EditWorkshopSession::class, ['record' => $session->getRouteKey()])
            ->fillForm(['location_name' => 'Обновлённое место'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Обновлённое место', $session->refresh()->location_name);
    }

    public function test_autofilled_price_is_saved_as_cents(): void
    {
        $masterclass = $this->makeMasterclass([
            'slug' => 'autofill-save',
            'included_participants' => 12,
            'price_cents' => 150050,
        ]);

        Livewire::test(CreateWorkshopSession::class)
            ->fillForm([
                'masterclass_id' => $masterclass->id,
                'starts_at' => now()->addDays(6)->setTime(12, 0)->format('Y-m-d H:i:s'),
            ])
            ->assertFormSet(['capacity' => 12, 'price_cents' => '1 500,50'])
            ->call('create')
            ->assertHasNoFormErrors();

        $session = WorkshopSession::query()->where('masterclass_id', $masterclass->id)->firstOrFail();

        $this->assertSame(12, $session->capacity);
        $this->assertSame(150050, $session->price_cents);
    }

    public function test_changing_masterclass_updates_capacity_and_price(): void
    {
        $first = $this->makeMasterclass([
            'slug' => 'autofill-first',
            'included_participants' => 8,
            'price_cents' => 120000,
        ]);
        $second = $this->makeMasterclass([
            'slug' => 'autofill-second',
            'included_participants' => 15,
            'price_cents' => 95000,
        ]);

        Livewire::test(CreateWorkshopSession::class)
            ->fillForm(['masterclass_id' => $first->id])
            ->assertFormSet(['capacity' => 8, 'price_cents' => '1 200,00'])
            ->fillForm(['masterclass_id' => $second->id])
            ->assertFormSet(['capacity' => 15, 'price_cents' => '950,00']);
    }

    public function test_invalid_masterclass_values_are_not_applied_and_user_is_notified(): void
    {
        $masterclass = $this->makeMasterclass(['slug' => 'autofill-invalid']);

        DB::table('masterclasses')->where('id', $masterclass->id)->update([
            'included_participants' => 0,
            'price_cents' => -100,
        ]);

        $expected = Notification::make()
            ->warning()
            ->title('Проверьте карточку мастер-класса')
            ->body('В карточке «'.$masterclass->title.'» исправьте: «Количество участников»; «Цена в студии». Некорректные значения в сеанс не подставлены.')
            ->persistent();

        Livewire::test(CreateWorkshopSession::class)
            ->fillForm(['masterclass_id' => $masterclass->id])
            ->assertNotified($expected)
            ->assertFormSet([
                'capacity' => 8,
                'price_cents' => null,
            ]);
    }

    public function test_only_valid_field_is_filled_when_other_field_is_invalid(): void
    {
        $masterclass = $this->makeMasterclass([
            'slug' => 'autofill-partially-invalid',
            'included_participants' => 11,
            'price_cents' => 100000,
        ]);

        DB::table('masterclasses')->where('id', $masterclass->id)->update([
            'price_cents' => -1,
        ]);

        Livewire::test(CreateWorkshopSession::class)
            ->fillForm(['masterclass_id' => $masterclass->id])
            ->assertNotified('Проверьте карточку мастер-класса')
            ->assertFormSet([
                'capacity' => 11,
                'price_cents' => null,
            ]);
    }

    public function test_capacity_cannot_drop_below_booked_seats_when_masterclass_changes(): void
    {
        $current = $this->makeMasterclass([
            'slug' => 'autofill-current',
            'included_participants' => 10,
            'price_cents' => 100000,
        ]);
        $smaller = $this->makeMasterclass([
            'slug' => 'autofill-smaller',
            'included_participants' => 2,
            'price_cents' => 90000,
        ]);

        $session = WorkshopSession::create([
            'masterclass_id' => $current->id,
            'starts_at' => now()->addDays(5)->setTime(15, 0),
            'timezone' => 'Europe/Moscow',
            'capacity' => 10,
            'price_cents' => 100000,
            'status' => 'scheduled',
            'is_published' => true,
        ]);

        Booking::create([
            'number' => 'BK-AUTOFILL-1',
            'workshop_session_id' => $session->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'participants_count' => 4,
            'contact_name' => 'Клиент',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'client@example.com',
            'unit_price_cents' => 100000,
            'total_cents' => 400000,
        ]);

        Livewire::test(EditWorkshopSession::class, ['record' => $session->getRouteKey()])
            ->fillForm(['masterclass_id' => $smaller->id])
            ->assertNotified('Вместимость меньше числа записанных')
            ->assertFormSet(['capacity' => 2])
            ->call('save')
            ->assertHasFormErrors(['capacity']);
    }

    public function test_missing_masterclass_is_reported_without_filling_fields(): void
    {
        Livewire::test(CreateWorkshopSession::class)
            ->fillForm(['masterclass_id' => 999999])
            ->assertNotified('Мастер-класс не найден')
            ->assertFormSet(['capacity' => 8, 'price_cents' => null]);
    }

    private function makeMasterclass(array $attributes = []): Masterclass
    {
        return Masterclass::query()->create(array_merge([
            'title' => 'Мастер-класс для автозаполнения',
            'slug' => 'autofill-'.uniqid(),
            'short_description' => 'Проверка автозаполнения сеанса',
            'price_cents' => 100000,
            'included_participants' => 10,
        ], $attributes));
    }
}
