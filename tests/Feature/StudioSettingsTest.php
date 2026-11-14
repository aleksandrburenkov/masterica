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
                'delivery_city_cents' => '400,00',
            ])
            ->fillForm([
                'delivery_city_cents' => '2 500,50',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(250050, (int) setting('rules.delivery_city_cents'));
    }
}
