<?php

namespace Tests\Feature;

use App\Filament\Resources\DistanceTariffs\Pages\CreateDistanceTariff;
use App\Models\DistanceTariff;
use App\Models\Masterclass;
use App\Models\OutreachRequest;
use App\Models\User;
use App\Services\OutreachCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DistanceTariffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_tariff_is_selected_by_interval_and_boundaries_are_inclusive(): void
    {
        $near = $this->tariff(10, 30, 50);
        $far = $this->tariff(31, 100, 100);

        $this->assertNull(DistanceTariff::forDistance(5));
        $this->assertTrue($near->is(DistanceTariff::forDistance(10)));
        $this->assertTrue($near->is(DistanceTariff::forDistance(30)));
        $this->assertTrue($far->is(DistanceTariff::forDistance(31)));
    }

    public function test_distance_beyond_last_tariff_uses_farthest_tariff(): void
    {
        $this->tariff(10, 30, 50);
        $far = $this->tariff(31, 100, 100);

        $this->assertTrue($far->is(DistanceTariff::forDistance(250)));
    }

    public function test_open_ended_tariff_covers_any_far_distance(): void
    {
        $this->tariff(10, 30, 50);
        $open = $this->tariff(31, null, 150);

        $this->assertTrue($open->is(DistanceTariff::forDistance(400)));
    }

    public function test_calculator_adds_surcharge_per_participant(): void
    {
        $this->tariff(10, 30, 50);

        $masterclass = Masterclass::query()->create([
            'title' => 'Выездной мастер-класс',
            'slug' => 'distance-outreach',
            'short_description' => 'Описание',
            'price_cents' => 100000,
            'outreach_price_cents' => 100000,
            'included_participants' => 10,
        ]);

        $calc = app(OutreachCalculator::class)->calculate(10, $masterclass, 24.4);

        $this->assertSame(1000000, $calc['base_price_cents']);
        $this->assertSame(24.4, $calc['distance_km']);
        $this->assertSame(5000, $calc['distance_surcharge_per_participant_cents']);
        $this->assertSame(50000, $calc['distance_surcharge_cents']);
        $this->assertSame(1050000, $calc['total_cents']);
    }

    public function test_city_distance_has_no_surcharge(): void
    {
        $this->tariff(10, 30, 50);

        $masterclass = Masterclass::query()->create([
            'title' => 'Выездной мастер-класс',
            'slug' => 'distance-city',
            'short_description' => 'Описание',
            'price_cents' => 100000,
            'outreach_price_cents' => 100000,
            'included_participants' => 10,
        ]);

        $calc = app(OutreachCalculator::class)->calculate(4, $masterclass, 7.5);

        $this->assertSame(0, $calc['distance_surcharge_cents']);
        $this->assertSame(400000, $calc['total_cents']);
    }

    public function test_outreach_request_stores_distance_and_surcharge(): void
    {
        $this->tariff(10, 30, 50);

        $this->post(route('outreach.store'), [
            'direction' => 'basket',
            'requested_date' => now()->addWeek()->toDateString(),
            'location' => 'Брянск, Почеп',
            'distance_km' => '24.4',
            'participants_count' => 10,
            'age_group' => 'Дети 8–10 лет',
            'contact_name' => 'Организатор',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'school@example.com',
            'consent_data' => '1',
        ])->assertRedirect(route('outreach.index'));

        $request = OutreachRequest::firstOrFail();

        $this->assertSame(24.4, $request->distance_km);
        $this->assertSame(50000, $request->distance_surcharge_cents);
        $this->assertSame(1550000, $request->calc_total_cents);
    }

    public function test_outreach_page_exposes_office_and_tariff_grid_to_calculator(): void
    {
        $this->tariff(10, 30, 50);

        $this->get(route('outreach.index'))
            ->assertOk()
            ->assertSee('data-office-lat="53.216894"', false)
            ->assertSee('data-office-lng="34.348181"', false)
            ->assertSee('data-distance-tariffs=', false)
            ->assertSee('data-yandex-geocoder=', false)
            ->assertSee('data-geocode-url=', false)
            ->assertSee('&quot;surcharge&quot;:50', false)
            ->assertSee('data-outreach-distance', false)
            ->assertSee('outreach-distance.js', false);
    }

    public function test_admin_can_manage_distance_tariffs(): void
    {
        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        Livewire::test(CreateDistanceTariff::class)
            ->fillForm([
                'min_distance' => 10,
                'max_distance' => 30,
                'surcharge_per_participant' => 50,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas((new DistanceTariff)->getTable(), [
            'min_distance' => 10,
            'max_distance' => 30,
            'surcharge_per_participant' => 50,
        ]);

        $this->get('/admin/distance-tariffs')
            ->assertOk()
            ->assertSee('10–30 км');
    }

    private function tariff(int $min, ?int $max, int $surcharge): DistanceTariff
    {
        return DistanceTariff::query()->create([
            'min_distance' => $min,
            'max_distance' => $max,
            'surcharge_per_participant' => $surcharge,
        ]);
    }
}
