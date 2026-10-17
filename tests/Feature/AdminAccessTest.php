<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_client_cannot_access_admin_panel(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)->get('/admin')->assertForbidden();
    }

    public function test_owner_can_access_admin_panel(): void
    {
        $owner = User::where('email', 'owner@korni.local')->firstOrFail();

        $this->actingAs($owner)->get('/admin')->assertOk();
    }

    public function test_owner_can_open_settings_page(): void
    {
        $owner = User::where('email', 'owner@korni.local')->firstOrFail();

        $this->actingAs($owner)->get('/admin/studio-settings')->assertOk();
    }

    public function test_client_cannot_open_settings_page(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)->get('/admin/studio-settings')->assertForbidden();
    }
}
