<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Новый Клиент',
            'email' => 'new@example.com',
            'phone' => '+7 900 000-00-01',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'consent_data' => '1',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'new@example.com')->first();
        $this->assertSame(UserRole::Client, $user->role);
    }

    public function test_registration_requires_consent(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Без согласия',
            'email' => 'noconsent@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertSessionHasErrors('consent_data');

        $this->assertGuest();
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret-password'),
            'role' => UserRole::Client,
        ]);

        $this->post(route('login.store'), [
            'login' => 'login@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'login2@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('login.store'), [
            'login' => 'login2@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_does_not_redirect_to_external_host(): void
    {
        User::factory()->create([
            'email' => 'ext@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('login.store'), [
            'login' => 'ext@example.com',
            'password' => 'secret-password',
            'redirect' => 'http://localhost.evil.com/phish',
        ])->assertRedirect(route('account.dashboard'));
    }

    public function test_login_honours_local_path_redirect(): void
    {
        User::factory()->create([
            'email' => 'local@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('login.store'), [
            'login' => 'local@example.com',
            'password' => 'secret-password',
            'redirect' => '/kabinet/zapisi',
        ])->assertRedirect('/kabinet/zapisi');
    }
}
