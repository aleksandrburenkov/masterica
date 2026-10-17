<?php

namespace Tests\Feature;

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

    public function test_base_group_quote(): void
    {
        $calc = app(OutreachCalculator::class)->calculate(8);

        $this->assertSame(800000, $calc['base_price_cents']);
        $this->assertSame(0, $calc['extra_count']);
        $this->assertSame(800000, $calc['total_cents']);
    }

    public function test_extra_participants_are_charged(): void
    {
        $calc = app(OutreachCalculator::class)->calculate(10);

        $this->assertSame(2, $calc['extra_count']);
        $this->assertSame(800000 + 2 * 70000, $calc['total_cents']);
    }

    public function test_outreach_request_stores_server_calculated_total(): void
    {
        $this->post(route('outreach.store'), [
            'direction' => 'Корзина из бумажной лозы',
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
        $this->assertSame(4, $request->extra_count);
        $this->assertSame(800000 + 4 * 70000, $request->calc_total_cents);
    }

    public function test_money_formatting(): void
    {
        $this->assertSame('1 500 ₽', money(150000));
        $this->assertSame('0 ₽', money(0));
        $this->assertSame('₽', currency_symbol('RUB'));
    }
}
