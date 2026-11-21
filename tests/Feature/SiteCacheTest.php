<?php

namespace Tests\Feature;

use App\Filament\Pages\StudioSettings;
use App\Models\LegalDocument;
use App\Models\Setting;
use App\Models\User;
use App\Support\LegalDocuments;
use App\Support\Settings;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class SiteCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Settings::flush();
        LegalDocuments::flush();
    }

    public function test_clearing_settings_cache_reloads_values_from_database(): void
    {
        $this->seed();

        Settings::set('studio.name', 'Старое название');

        $this->assertSame('Старое название', Settings::get('studio.name'));

        Setting::query()->where('key', 'studio.name')->update(['value' => 'Новое название']);

        $this->assertSame('Старое название', Settings::get('studio.name'));

        SiteCache::clearSettings();

        $this->assertSame('Новое название', Settings::get('studio.name'));
    }

    public function test_clearing_legal_documents_cache_reloads_documents_from_database(): void
    {
        $this->seedLegalDocuments();

        LegalDocuments::all();

        LegalDocument::query()->where('key', 'privacy_policy')->update(['title' => 'Новый заголовок']);

        $this->assertNotSame('Новый заголовок', LegalDocuments::get('privacy_policy')?->title);

        SiteCache::clearLegalDocuments();

        $this->assertSame('Новый заголовок', LegalDocuments::get('privacy_policy')?->title);
    }

    public function test_clearing_geocoding_cache_keeps_other_cache_entries(): void
    {
        config(['cache.default' => 'database']);
        app('cache')->forgetDriver('database');

        Cache::put('geocode.53.243600,34.363400', ['lat' => 53.2436, 'lng' => 34.3634], 3600);
        Cache::put('site.other.key', 'keep', 3600);

        $this->assertTrue(SiteCache::canClearGeocoding());

        $this->assertSame(1, SiteCache::clearGeocoding());

        $this->assertFalse(Cache::has('geocode.53.243600,34.363400'));
        $this->assertTrue(Cache::has('site.other.key'));
    }

    public function test_clearing_all_cache_removes_application_entries(): void
    {
        Cache::put('site.test.key', 'value', 3600);

        SiteCache::clearAll();

        $this->assertFalse(Cache::has('site.test.key'));
    }

    public function test_owner_can_clear_content_cache_from_studio_settings(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        Cache::put('site.test.key', 'value', 3600);

        Livewire::test(StudioSettings::class)
            ->assertSee('Кеш сайта')
            ->call('clearContentCache')
            ->assertNotified('Кеш контента и настроек — готово');

        $this->assertFalse(Cache::has('site.test.key'));
    }

    public function test_owner_can_clear_filament_cache_from_studio_settings(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        Livewire::test(StudioSettings::class)
            ->call('clearFilamentCache')
            ->assertNotified('Кеш компонентов админ-панели — готово');
    }

    public function test_owner_can_clear_everything_from_studio_settings(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());

        Livewire::test(StudioSettings::class)
            ->call('clearEverything')
            ->assertNotified('Полный сброс кеша — готово');
    }

    public function test_manager_cannot_access_studio_settings_or_clear_cache(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'manager@korni.local')->firstOrFail());

        $this->assertFalse(StudioSettings::canAccess());

        $this->get('/admin/studio-settings')->assertForbidden();
    }
}
