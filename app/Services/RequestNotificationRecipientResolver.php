<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;

/**
 * Определяет адреса получателей писем о новых обращениях.
 *
 * Приоритет для владельца:
 * 1. корректный альтернативный email из настроек;
 * 2. email пользователя с ролью «владелец» (fallback);
 * 3. если оба варианта пусты или некорректны — владелец писем не получает.
 *
 * Получатели из списка рассылки добавляются к адресу владельца.
 */
class RequestNotificationRecipientResolver
{
    public const ALTERNATE_EMAIL_SETTING = 'notifications.alternate_owner_email';

    /** @return array<int, string> */
    public function resolve(): array
    {
        $emails = [];

        $owner = $this->ownerEmail();
        if ($owner !== null) {
            $emails[mb_strtolower($owner)] = true;
        }

        foreach ($this->subscriberEmails() as $email) {
            $emails[mb_strtolower($email)] = true;
        }

        return array_keys($emails);
    }

    public function ownerEmail(): ?string
    {
        $alternate = trim((string) Settings::get(self::ALTERNATE_EMAIL_SETTING, ''));

        if ($alternate !== '' && self::isValidEmail($alternate)) {
            return $alternate;
        }

        if ($alternate !== '') {
            Log::warning('[request-notification] Некорректный альтернативный email владельца, используется основной адрес.', [
                'alternate_email' => $alternate,
            ]);
        }

        $ownerEmail = trim((string) User::query()
            ->where('role', UserRole::Owner->value)
            ->orderBy('id')
            ->value('email'));

        if (! self::isValidEmail($ownerEmail)) {
            Log::warning('[request-notification] Не найден корректный email владельца.');

            return null;
        }

        return $ownerEmail;
    }

    /** @return array<int, string> */
    public function subscriberEmails(): array
    {
        $emails = [];

        foreach (NotificationRecipient::query()->with('user')->get() as $recipient) {
            $email = trim((string) $recipient->user?->email);

            if (self::isValidEmail($email)) {
                $emails[$email] = true;
            } else {
                Log::warning('[request-notification] Пропущен получатель с некорректным email.', [
                    'recipient_id' => $recipient->id,
                    'user_id' => $recipient->user_id,
                    'email' => $email,
                ]);
            }
        }

        return array_keys($emails);
    }

    public static function isValidEmail(?string $email): bool
    {
        $email = trim((string) $email);

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
