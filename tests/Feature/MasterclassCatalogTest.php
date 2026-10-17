<?php

namespace Tests\Feature;

use App\Models\Masterclass;
use App\Models\Technique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterclassCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_catalog_renders_all_cards_with_client_filter_hooks(): void
    {
        $technique = Technique::query()->where('name', 'Канзаши')->firstOrFail();
        $total = Masterclass::query()->published()->count();

        $response = $this->get(route('masterclasses.index'));

        $response->assertOk()
            ->assertSee('data-filter-techniques', false)
            ->assertSee('data-filter-form', false)
            ->assertSee('data-catalog-grid', false)
            ->assertSee('data-catalog-empty', false)
            ->assertSee('data-technique="'.$technique->slug.'"', false)
            ->assertSee('masterclass-catalog.js', false);

        $this->assertGreaterThan(0, $total);
        $this->assertSame($total, substr_count($response->getContent(), 'catalog-card__media'));
    }

    public function test_catalog_ignores_get_parameters(): void
    {
        $total = Masterclass::query()->published()->count();

        $response = $this->get(route('masterclasses.index', ['technique' => 'does-not-exist', 'format' => 'outreach']));

        $response->assertOk();

        $this->assertSame($total, substr_count($response->getContent(), 'catalog-card__media'));
    }
}
