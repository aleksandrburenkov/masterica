<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Notifications\AdminResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::Manager,
            'email' => 'manager@example.com',
        ], $attributes));
    }

    private function issueToken(User $user): string
    {
        return Password::broker('users')->createToken($user);
    }

    public function test_admin_login_page_contains_forgot_password_link(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Забыли пароль?')
            ->assertSee(route('filament.admin.auth.password-reset.request'), false);
    }

    public function test_password_reset_request_page_loads(): void
    {
        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_password_reset_notification_is_sent_without_a_queue_worker(): void
    {
        $notification = app(AdminResetPasswordNotification::class, ['token' => 'test-token']);

        $this->assertNotInstanceOf(ShouldQueue::class, $notification);
    }

    public function test_password_reset_email_is_fully_localized(): void
    {
        $staff = $this->staff();
        $notification = app(AdminResetPasswordNotification::class, ['token' => 'test-token']);
        $notification->url = 'http://masterica/admin/password-reset/reset/test-token';

        $html = (string) $notification->toMail($staff)->render();

        $this->assertStringContainsString('С уважением, администратор сайта', $html);
        $this->assertStringContainsString('Все права защищены.', $html);
        $this->assertStringContainsString('Если кнопка «Сбросить пароль» не работает', $html);
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString("If you're having trouble", $html);
    }

    public function test_staff_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $staff = $this->staff();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $staff->email])
            ->call('request')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($staff, AdminResetPasswordNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $staff->email]);
    }

    public function test_unknown_email_does_not_receive_a_reset_link_or_create_a_token(): void
    {
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'missing@example.com'])
            ->call('request')
            ->assertHasNoFormErrors();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'missing@example.com']);
    }

    public function test_staff_can_reset_password_with_a_valid_token(): void
    {
        $staff = $this->staff();
        $token = $this->issueToken($staff);

        Livewire::test(ResetPassword::class, [
            'email' => $staff->email,
            'token' => $token,
        ])
            ->fillForm([
                'password' => 'new-secret-password',
                'passwordConfirmation' => 'new-secret-password',
            ])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-secret-password', $staff->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $staff->email]);
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $staff = $this->staff();
        $token = $this->issueToken($staff);

        Livewire::test(ResetPassword::class, [
            'email' => $staff->email,
            'token' => $token,
        ])
            ->fillForm([
                'password' => 'new-secret-password',
                'passwordConfirmation' => 'new-secret-password',
            ])
            ->call('resetPassword');

        Livewire::test(ResetPassword::class, [
            'email' => $staff->email,
            'token' => $token,
        ])
            ->fillForm([
                'password' => 'another-password',
                'passwordConfirmation' => 'another-password',
            ])
            ->call('resetPassword');

        $this->assertTrue(Hash::check('new-secret-password', $staff->fresh()->password));
        $this->assertFalse(Hash::check('another-password', $staff->fresh()->password));
    }

    public function test_expired_token_is_rejected(): void
    {
        $staff = $this->staff();
        $token = $this->issueToken($staff);
        $originalPassword = $staff->password;

        DB::table('password_reset_tokens')
            ->where('email', $staff->email)
            ->update([
                'created_at' => now()->subMinutes((int) config('auth.passwords.users.expire') + 1),
            ]);

        Livewire::test(ResetPassword::class, [
            'email' => $staff->email,
            'token' => $token,
        ])
            ->fillForm([
                'password' => 'new-secret-password',
                'passwordConfirmation' => 'new-secret-password',
            ])
            ->call('resetPassword');

        $this->assertSame($originalPassword, $staff->fresh()->password);
        $this->assertFalse(Hash::check('new-secret-password', $staff->fresh()->password));
    }

    public function test_creating_an_admin_clears_stale_tokens_for_the_same_email(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => 'stale@example.com',
            'token' => Hash::make('stale-token'),
            'created_at' => now(),
        ]);

        $this->staff(['email' => 'stale@example.com']);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'stale@example.com']);
    }

    public function test_changing_an_admin_email_revokes_the_existing_token(): void
    {
        $staff = $this->staff();
        $this->issueToken($staff);

        $staff->update(['email' => 'new-manager@example.com']);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'manager@example.com']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'new-manager@example.com']);
    }

    public function test_changing_an_admin_password_revokes_the_existing_token(): void
    {
        $staff = $this->staff();
        $this->issueToken($staff);

        $staff->update(['password' => 'new-secret-password']);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $staff->email]);
    }

    public function test_changing_an_admin_role_revokes_the_existing_token(): void
    {
        $staff = $this->staff();
        $this->issueToken($staff);

        $staff->update(['role' => UserRole::Client]);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $staff->email]);
    }

    public function test_deleting_an_admin_revokes_the_existing_token(): void
    {
        $staff = $this->staff();
        $this->issueToken($staff);

        $staff->delete();

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'manager@example.com']);
    }

    public function test_password_change_from_the_admin_edit_page_revokes_the_existing_token(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);
        $staff = $this->staff();
        $this->issueToken($staff);

        $this->actingAs($owner);

        Livewire::test(EditUser::class, ['record' => $staff->getRouteKey()])
            ->fillForm(['password' => 'new-secret-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-secret-password', $staff->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $staff->email]);
    }
}
