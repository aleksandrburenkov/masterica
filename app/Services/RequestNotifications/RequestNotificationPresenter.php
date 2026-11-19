<?php

namespace App\Services\RequestNotifications;

use Illuminate\Database\Eloquent\Model;

/**
 * Преобразует модель обращения в данные письма. Новый тип обращения
 * подключается регистрацией презентера в RequestNotificationService.
 */
interface RequestNotificationPresenter
{
    public function present(Model $request): RequestNotificationData;
}
