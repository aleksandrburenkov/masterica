<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Local-only notification channel. Writes to the in-app journal and the
 * application log; never sends real e-mail, SMS or third-party messages.
 */
class NotificationService
{
    public function notify(?User $user, string $title, ?string $body = null, string $channel = 'app'): void
    {
        if ($user !== null) {
            AppNotification::create([
                'user_id' => $user->id,
                'channel' => $channel,
                'title' => $title,
                'body' => $body,
            ]);
        }

        Log::channel(config('logging.default'))->info('[notification] '.$title, [
            'user_id' => $user?->id,
            'body' => $body,
        ]);
    }
}
