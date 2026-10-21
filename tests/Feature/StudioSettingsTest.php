<?php

namespace Tests\Feature;

use App\Filament\Pages\StudioSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudioSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_settings_are_shown_in_rubles_and_stored_in_kopecks(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        Livewire::test(StudioSettings::class)
            ->assertFormSet([
                'workshop_price_cents' => '1 500,00',
                'outreach_base_cents' => '8 000,00',
                'outreach_extra_cents' => '700,00',
                'delivery_pickup_cents' => '0,00',
                'delivery_city_cents' => '400,00',
            ])
            ->fillForm([
                'workshop_price_cents' => '2 500,50',
                'outreach_base_cents' => '8 500,00',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(250050, (int) setting('pricing.workshop_price_cents'));
        $this->assertSame(850000, (int) setting('pricing.outreach_base_cents'));
    }
}
