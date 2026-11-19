<?php

namespace App\Services\RequestNotifications\Presenters;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\RequestNotifications\RequestNotificationData;
use App\Services\RequestNotifications\RequestNotificationPresenter;
use Illuminate\Database\Eloquent\Model;

class BookingRequestNotificationPresenter implements RequestNotificationPresenter
{
    public function present(Model $request): RequestNotificationData
    {
        /** @var Booking $request */
        $session = $request->session;
        $masterclass = $session?->masterclass;

        return new RequestNotificationData(
            type: 'Запись на мастер-класс',
            number: (string) $request->number,
            adminUrl: BookingResource::getUrl('edit', ['record' => $request]),
            fields: [
                $this->field('Дата и время обращения', $this->dateTime($request->created_at)),
                $this->field('Номер заявки', (string) $request->number),
                $this->field('Мастер-класс', $masterclass?->title ?? '—'),
                $this->field('Дата и время сеанса', $this->dateTime($session?->starts_at)),
                $this->field('Место проведения', $this->location($session)),
                $this->field('Количество участников', (string) $request->participants_count),
                $this->field('Возраст ребёнка', $request->child_age !== null ? $request->child_age.' лет' : '—'),
                $this->field('Стоимость', money((int) $request->total_cents, (string) ($request->currency ?? 'RUB'))),
                $this->field('Статус', $request->status?->label() ?? '—'),
                $this->field('Имя контакта', (string) $request->contact_name),
                $this->field('Телефон', (string) $request->contact_phone),
                $this->field('Email', (string) $request->contact_email),
                $this->field('Комментарий', (string) ($request->notes ?: '—')),
                $this->field('Согласие на обработку данных', $request->consent_data ? 'Да' : 'Нет'),
                $this->field('Согласие на рассылку', $request->consent_marketing ? 'Да' : 'Нет'),
                $this->field('Источник', (string) ($request->source ?: '—')),
            ],
        );
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

    private function location(mixed $session): string
    {
        if ($session === null) {
            return '—';
        }

        $parts = array_filter([
            trim((string) $session->location_name),
            trim((string) $session->address),
        ]);

        return $parts === [] ? '—' : implode(', ', $parts);
    }
}
