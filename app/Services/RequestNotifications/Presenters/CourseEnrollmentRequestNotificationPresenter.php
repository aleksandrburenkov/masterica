<?php

namespace App\Services\RequestNotifications\Presenters;

use App\Filament\Resources\CourseEnrollments\CourseEnrollmentResource;
use App\Models\CourseEnrollment;
use App\Services\RequestNotifications\RequestNotificationData;
use App\Services\RequestNotifications\RequestNotificationPresenter;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollmentRequestNotificationPresenter implements RequestNotificationPresenter
{
    public function present(Model $request): RequestNotificationData
    {
        /** @var CourseEnrollment $request */
        $request->loadMissing(['user', 'course']);
        $course = $request->course;
        $user = $request->user;

        return new RequestNotificationData(
            type: 'Заявка на онлайн-курс',
            number: $this->number($request),
            adminUrl: CourseEnrollmentResource::getUrl('edit', ['record' => $request]),
            fields: [
                $this->field('Дата и время заявки', $this->dateTime($request->created_at)),
                $this->field('Номер заявки', $this->number($request)),
                $this->field('Курс', $course?->title ?? '—'),
                $this->field('Стоимость курса', $course ? money((int) $course->price_cents, (string) ($course->currency ?? 'RUB')) : '—'),
                $this->field('Доступ предоставляется на', $course ? $course->access_days.' дней' : '—'),
                $this->field('Пользователь', $user?->name ?? '—'),
                $this->field('Email пользователя', $user?->email ?? '—'),
                $this->field('Телефон пользователя', (string) ($user?->phone ?: '—')),
                $this->field('Статус заявки', $request->status?->label() ?? '—'),
                $this->field('Статус оплаты', $request->payment_status?->label() ?? '—'),
            ],
        );
    }

    private function number(CourseEnrollment $request): string
    {
        return 'EN-'.str_pad((string) $request->getKey(), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{label: string, value: string}
     */
    private function field(string $label, string $value): array
    {
        return ['label' => $label, 'value' => $value];
    }

    private function dateTime(mixed $value): string
    {
        return $value?->format('d.m.Y H:i') ?? '—';
    }
}
