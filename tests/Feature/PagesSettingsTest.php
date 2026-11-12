<?php

namespace Tests\Feature;

use App\Filament\Pages\PagesSettings;
use App\Models\ContentPage;
use App\Models\SitePage;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PagesSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_contains_static_content_and_legal_pages(): void
    {
        $this->seed();

        $this->assertDatabaseHas('site_pages', ['key' => 'about', 'route_name' => 'about']);
        $this->assertDatabaseHas('site_pages', ['key' => 'contacts', 'route_name' => 'contacts']);

        $page = ContentPage::firstOrFail();
        $this->assertDatabaseHas('site_pages', [
            'source_type' => ContentPage::class,
            'source_id' => $page->getKey(),
            'route_name' => 'guides.show',
        ]);
    }

    public function test_new_content_page_appears_in_registry_automatically(): void
    {
        $this->seed();

        $page = ContentPage::query()->create([
            'title' => 'Новый материал',
            'slug' => 'novyj-material',
            'type' => 'guide',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('site_pages', [
            'source_type' => ContentPage::class,
            'source_id' => $page->getKey(),
            'title' => 'Новый материал',
        ]);
    }

    public function test_active_page_shows_normally(): void
    {
        $this->seed();

        $page = ContentPage::firstOrFail();

        $this->get(route('guides.show', $page->slug))
            ->assertOk()
            ->assertSee($page->title);
    }

    public function test_inactive_content_page_shows_maintenance_text(): void
    {
        $this->seed();

        $page = ContentPage::firstOrFail();
        $page->sitePage->update(['is_active' => false]);

        $this->get(route('guides.show', $page->slug))
            ->assertStatus(503)
            ->assertSee(setting('pages.maintenance_text'));
    }

    public function test_inactive_static_page_shows_maintenance_text(): void
    {
        $this->seed();

        SitePage::query()->where('key', 'about')->update(['is_active' => false]);

        $this->get(route('about'))
            ->assertStatus(503)
            ->assertSee(setting('pages.maintenance_text'));
    }

    public function test_inactive_content_page_is_hidden_from_listings_and_sitemap(): void
    {
        $this->seed();

        $page = ContentPage::firstOrFail();
        $page->sitePage->update(['is_active' => false]);

        $this->get(route('guides.index'))->assertOk()->assertDontSee($page->title);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee(route('guides.show', $page->slug));
    }

    public function test_owner_can_edit_maintenance_text_and_toggle_pages(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        $sitePage = SitePage::query()->where('key', 'about')->firstOrFail();

        Livewire::test(PagesSettings::class)
            ->assertFormSet(['maintenance_text' => setting('pages.maintenance_text')])
            ->fillForm(['maintenance_text' => 'Ведутся работы'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Ведутся работы', setting('pages.maintenance_text'));

        Livewire::test(PagesSettings::class)
            ->call('updateTableColumnState', 'is_active', (string) $sitePage->getKey(), false);

        $this->assertFalse($sitePage->fresh()->is_active);
    }

    public function test_manager_cannot_access_pages_settings(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'manager@korni.local')->firstOrFail());

        $this->assertFalse(PagesSettings::canAccess());
    }

    public function test_global_maintenance_hides_every_page_for_visitors(): void
    {
        $this->seed();

        Settings::set('pages.maintenance_enabled', '1', 'pages', 'bool');

        foreach (['home', 'about', 'contacts', 'guides.index', 'shop.index', 'courses.index'] as $route) {
            $this->get(route($route))->assertStatus(503)->assertSee(setting('pages.maintenance_text'));
        }
    }

    public function test_global_maintenance_persists_between_requests(): void
    {
        $this->seed();

        Settings::set('pages.maintenance_enabled', '1', 'pages', 'bool');

        $this->get(route('about'))->assertStatus(503);
        $this->get(route('contacts'))->assertStatus(503);
        $this->assertTrue(Settings::bool('pages.maintenance_enabled'));
    }

    public function test_global_maintenance_keeps_login_available(): void
    {
        $this->seed();

        Settings::set('pages.maintenance_enabled', '1', 'pages', 'bool');

        $this->get(route('login'))->assertOk();
    }

    public function test_owner_sees_site_while_global_maintenance_is_enabled(): void
    {
        $this->seed();

        Settings::set('pages.maintenance_enabled', '1', 'pages', 'bool');

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        $this->get(route('home'))->assertOk();
        $this->get(route('about'))->assertOk();
        $this->get(route('guides.index'))->assertOk();
    }

    public function test_owner_sees_inactive_page_while_visitors_do_not(): void
    {
        $this->seed();

        $page = ContentPage::firstOrFail();
        $page->sitePage->update(['is_active' => false]);

        $this->get(route('guides.show', $page->slug))->assertStatus(503);

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());
        $this->get(route('guides.show', $page->slug))->assertOk()->assertSee($page->title);
    }

    public function test_owner_can_toggle_global_maintenance_from_admin(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        Livewire::test(PagesSettings::class)
            ->assertFormSet(['maintenance_enabled' => false])
            ->fillForm(['maintenance_enabled' => true, 'maintenance_text' => 'Идут работы'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Settings::bool('pages.maintenance_enabled'));
        $this->assertSame('Идут работы', setting('pages.maintenance_text'));
    }
}
