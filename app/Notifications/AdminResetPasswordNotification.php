<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPasswordNotification extends BaseResetPasswordNotification
{
    public string $url = '';

    protected function resetUrl($notifiable): string
    {
        return $this->url;
    }

    public function toMail($notifiable): MailMessage
    {
        $expireMinutes = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60,
        );

        return (new MailMessage)
            ->subject('Сброс пароля в административной панели')
            ->greeting('Здравствуйте!')
            ->line('Вы запросили сброс пароля для входа в административную панель студии.')
            ->action('Сбросить пароль', $this->url)
            ->line("Ссылка действует {$expireMinutes} мин. и может быть использована только один раз.")
            ->line('Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.')
            ->salutation('С уважением, администратор сайта');
    }
}
