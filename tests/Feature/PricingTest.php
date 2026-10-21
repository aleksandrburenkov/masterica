<?php

namespace Tests\Feature;

use App\Models\Masterclass;
use App\Models\OutreachRequest;
use App\Services\OutreachCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_quote_is_empty_without_masterclass(): void
    {
        $calc = app(OutreachCalculator::class)->calculate(12);

        $this->assertSame(0, $calc['base_price_cents']);
        $this->assertSame(0, $calc['extra_count']);
        $this->assertSame(0.0, $calc['percent']);
        $this->assertSame(0, $calc['total_cents']);
    }

    public function test_base_group_quote_uses_masterclass_outreach_price(): void
    {
        $masterclass = $this->outreachMasterclass();

        $calc = app(OutreachCalculator::class)->calculate(10, $masterclass);

        $this->assertSame(800000, $calc['base_price_cents']);
        $this->assertSame(0, $calc['extra_count']);
        $this->assertSame(0.0, $calc['percent']);
        $this->assertSame(800000, $calc['total_cents']);
    }

    public function test_base_quote_multiplies_price_by_participants(): void
    {
        $masterclass = $this->outreachMasterclass();

        $this->assertSame(80000, app(OutreachCalculator::class)->calculate(1, $masterclass)['base_price_cents']);
        $this->assertSame(240000, app(OutreachCalculator::class)->calculate(3, $masterclass)['base_price_cents']);
    }

    public function test_participants_below_card_limit_have_no_surcharge(): void
    {
        $masterclass = $this->outreachMasterclass();

        $calc = app(OutreachCalculator::class)->calculate(8, $masterclass);

        $this->assertSame(640000, $calc['base_price_cents']);
        $this->assertSame(0, $calc['extra_count']);
        $this->assertSame(0.0, $calc['percent']);
        $this->assertSame(640000, $calc['total_cents']);
    }

    public function test_extra_participants_add_configured_percent(): void
    {
        $masterclass = $this->outreachMasterclass();

        $calc = app(OutreachCalculator::class)->calculate(12, $masterclass);

        $this->assertSame(960000, $calc['base_price_cents']);
        $this->assertSame(2, $calc['extra_count']);
        $this->assertSame(5.0, $calc['percent']);
        $this->assertSame(48000, $calc['surcharge_cents']);
        $this->assertSame(1008000, $calc['total_cents']);
    }

    public function test_next_percent_tier_is_used_for_more_extra_participants(): void
    {
        $masterclass = $this->outreachMasterclass();

        $calc = app(OutreachCalculator::class)->calculate(15, $masterclass);

        $this->assertSame(1200000, $calc['base_price_cents']);
        $this->assertSame(5, $calc['extra_count']);
        $this->assertSame(7.0, $calc['percent']);
        $this->assertSame(84000, $calc['surcharge_cents']);
        $this->assertSame(1284000, $calc['total_cents']);
    }

    public function test_largest_tier_is_used_above_the_last_threshold(): void
    {
        $masterclass = $this->outreachMasterclass();

        $calc = app(OutreachCalculator::class)->calculate(25, $masterclass);

        $this->assertSame(2000000, $calc['base_price_cents']);
        $this->assertSame(15, $calc['extra_count']);
        $this->assertSame(7.0, $calc['percent']);
        $this->assertSame(140000, $calc['surcharge_cents']);
        $this->assertSame(2140000, $calc['total_cents']);
    }

    public function test_outreach_request_stores_server_calculated_total(): void
    {
        $masterclass = $this->outreachMasterclass();

        $this->post(route('outreach.store'), [
            'direction' => $masterclass->slug,
            'requested_date' => now()->addWeek()->toDateString(),
            'location' => 'Брянск, школа №1',
            'participants_count' => 12,
            'age_group' => 'Дети 8–10 лет',
            'contact_name' => 'Организатор',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'school@example.com',
            'consent_data' => '1',
        ])->assertRedirect(route('outreach.index'));

        $request = OutreachRequest::firstOrFail();
        $this->assertSame($masterclass->title, $request->direction);
        $this->assertSame(2, $request->extra_count);
        $this->assertSame(48000, $request->extra_price_cents);
        $this->assertSame(1008000, $request->calc_total_cents);
    }

    public function test_outreach_request_accepts_direction_title(): void
    {
        $this->post(route('outreach.store'), $this->validPayload(['direction' => 'Корзина из бумажной лозы']))
            ->assertRedirect(route('outreach.index'));

        $this->assertDatabaseHas((new OutreachRequest)->getTable(), [
            'direction' => 'Корзина из бумажной лозы',
        ]);
    }

    public function test_outreach_request_redirects_back_to_source_page(): void
    {
        $this->post(route('outreach.store'), $this->validPayload(['source' => '/']))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount((new OutreachRequest)->getTable(), 1);
    }

    public function test_outreach_request_returns_json_for_ajax_submissions(): void
    {
        $response = $this->postJson(route('outreach.store'), $this->validPayload(['source' => '/']));

        $response->assertCreated()
            ->assertJsonStructure(['message', 'number']);

        $this->assertDatabaseCount((new OutreachRequest)->getTable(), 1);
    }

    public function test_money_formatting(): void
    {
        $this->assertSame('1 500 ₽', money(150000));
        $this->assertSame('0 ₽', money(0));
        $this->assertSame('₽', currency_symbol('RUB'));
    }

    private function outreachMasterclass(): Masterclass
    {
        return Masterclass::query()->create([
            'title' => 'Выездной мастер-класс',
            'slug' => 'outreach-test',
            'short_description' => 'Тестовый выездной мастер-класс',
            'duration_minutes' => 120,
            'price_cents' => 500000,
            'outreach_price_cents' => 80000,
            'included_participants' => 10,
            'participant_surcharges' => ['3' => '5', '5' => '7'],
            'is_published' => true,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'direction' => 'basket',
            'requested_date' => now()->addWeek()->toDateString(),
            'location' => 'Брянск, школа №1',
            'participants_count' => 12,
            'age_group' => 'Дети 8–10 лет',
            'contact_name' => 'Организатор',
            'contact_phone' => '+7 900 000-00-00',
            'contact_email' => 'school@example.com',
            'consent_data' => '1',
        ], $overrides);
    }
}
