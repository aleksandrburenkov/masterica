<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_layout_loads_sidebar_scroll_lock_assets(): void
    {
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Owner]));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('css/admin-sidebar.css', false)
            ->assertSee('js/admin-sidebar-scroll-lock.js', false);
    }
}
