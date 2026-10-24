<?php

namespace Tests\Feature;

use App\Filament\Resources\Masterclasses\Pages\CreateMasterclass;
use App\Models\Masterclass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterclassOutreachPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_card_saves_outreach_price_participants_and_percent_tiers(): void
    {
        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        Livewire::test(CreateMasterclass::class)
            ->fillForm([
                'title' => 'Выездной мастер-класс',
                'slug' => 'outreach-card',
                'short_description' => 'Описание выездного мастер-класса',
                'price_cents' => '1 500',
                'outreach_price_cents' => '8 000',
                'included_participants' => 10,
                'participant_surcharges' => ['3' => '5', '5' => '7'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $masterclass = Masterclass::query()->where('slug', 'outreach-card')->firstOrFail();

        $this->assertSame(150000, $masterclass->price_cents);
        $this->assertSame(800000, $masterclass->outreach_price_cents);
        $this->assertSame(10, $masterclass->included_participants);
        $this->assertSame(['3' => '5', '5' => '7'], $masterclass->participant_surcharges);
    }

    public function test_outreach_price_falls_back_to_regular_price(): void
    {
        $masterclass = Masterclass::query()->create([
            'title' => 'Без отдельной цены',
            'slug' => 'no-outreach-price',
            'short_description' => 'Описание',
            'price_cents' => 120000,
            'outreach_price_cents' => null,
        ]);

        $this->assertSame(120000, $masterclass->outreachPriceCents());
    }

    public function test_calculator_starts_with_zeros_and_uses_placeholders(): void
    {
        $response = $this->get(route('outreach.index'))->assertOk();

        $response->assertSee('placeholder="Введите количество участников"', false)
            ->assertSee('placeholder="Введите город или район проведения мероприятия"', false)
            ->assertSee('placeholder="Введите возраст, например: Дети 7-12 лет"', false)
            ->assertSee('Группа из 0 участников', false)
            ->assertSee('data-outreach-total>0 ₽', false);
    }

    public function test_calculator_options_expose_masterclass_pricing_data(): void
    {
        $masterclass = Masterclass::query()->create([
            'title' => 'Выездной мастер-класс',
            'slug' => 'outreach-options',
            'short_description' => 'Описание',
            'duration_minutes' => 120,
            'price_cents' => 500000,
            'outreach_price_cents' => 800000,
            'included_participants' => 10,
            'participant_surcharges' => ['3' => '5'],
        ]);

        $response = $this->get(route('outreach.index'))->assertOk();

        $response->assertSee('value="'.$masterclass->slug.'"', false)
            ->assertSee('data-price="800000"', false)
            ->assertSee('data-included="10"', false)
            ->assertSee('data-duration="120"', false)
            ->assertSee('data-surcharges="{&quot;3&quot;:&quot;5&quot;}"', false);
    }
}
