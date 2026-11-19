<?php

namespace App\Services;

use App\Mail\NewRequestNotificationMail;
use App\Models\Booking;
use App\Models\ContactRequest;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\OutreachRequest;
use App\Services\RequestNotifications\Presenters\BookingRequestNotificationPresenter;
use App\Services\RequestNotifications\Presenters\ContactRequestNotificationPresenter;
use App\Services\RequestNotifications\Presenters\CourseEnrollmentRequestNotificationPresenter;
use App\Services\RequestNotifications\Presenters\OrderRequestNotificationPresenter;
use App\Services\RequestNotifications\Presenters\OutreachRequestNotificationPresenter;
use App\Services\RequestNotifications\RequestNotificationData;
use App\Services\RequestNotifications\RequestNotificationPresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

/**
 * Единая точка отправки писем администраторам о любом новом обращении.
 *
 * Новый тип обращения подключается добавлением презентера в карту
 * PRESENTERS; логика получателей, отправки и обработки ошибок общая.
 */
class RequestNotificationService
{
    /** @var array<class-string<Model>, class-string<RequestNotificationPresenter>> */
    private const PRESENTERS = [
        Booking::class => BookingRequestNotificationPresenter::class,
        OutreachRequest::class => OutreachRequestNotificationPresenter::class,
        ContactRequest::class => ContactRequestNotificationPresenter::class,
        CourseEnrollment::class => CourseEnrollmentRequestNotificationPresenter::class,
        Order::class => OrderRequestNotificationPresenter::class,
    ];

    public function __construct(
        private readonly RequestNotificationRecipientResolver $recipients,
    ) {}

    /**
     * Отправляет письмо сразу, синхронно. Ошибки не прерывают пользовательский
     * сценарий: они логируются и не выбрасываются наружу.
     */
    public function send(Model $request): void
    {
        try {
            $data = $this->present($request);
            $emails = $this->recipients->resolve();
        } catch (Throwable $exception) {
            Log::error('[request-notification] Не удалось подготовить уведомление.', [
                'request_type' => $request::class,
                'request_id' => $request->getKey(),
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if ($emails === []) {
            Log::warning('[request-notification] Нет получателей для уведомления.', [
                'request_type' => $request::class,
                'request_id' => $request->getKey(),
                'number' => $data->number,
            ]);

            return;
        }

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new NewRequestNotificationMail($data));
            } catch (Throwable $exception) {
                Log::error('[request-notification] Ошибка отправки письма.', [
                    'email' => $email,
                    'request_type' => $request::class,
                    'request_id' => $request->getKey(),
                    'number' => $data->number,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function present(Model $request): RequestNotificationData
    {
        $presenter = self::PRESENTERS[$request::class] ?? null;

        if ($presenter === null) {
            throw new InvalidArgumentException('Нет презентера письма для модели '.$request::class);
        }

        return app($presenter)->present($request);
    }
}
