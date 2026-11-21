<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserPasswordResetObserver
{
    public function created(User $user): void
    {
        // A re-created account must not inherit a stale reset link from a previous record.
        $this->forgetTokens($user->getEmailForPasswordReset());
    }

    public function updated(User $user): void
    {
        if (! $user->wasChanged(['email', 'password', 'role'])) {
            return;
        }

        // Revoke links issued for both the previous and the current email address.
        $this->forgetTokens($user->getOriginal('email'));
        $this->forgetTokens($user->getEmailForPasswordReset());
    }

    public function deleted(User $user): void
    {
        $this->forgetTokens($user->getEmailForPasswordReset());
    }

    private function forgetTokens(?string $email): void
    {
        if (blank($email)) {
            return;
        }

        foreach ($this->tokenTables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->where('email', $email)->delete();
        }
    }

    /**
     * @return array<int, string>
     */
    private function tokenTables(): array
    {
        return collect(config('auth.passwords', []))
            ->pluck('table')
            ->filter(fn ($table): bool => is_string($table) && filled($table))
            ->unique()
            ->values()
            ->all();
    }
}
