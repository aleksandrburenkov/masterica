<?php

namespace App\Services\RequestNotifications\Presenters;

use App\Filament\Resources\OutreachRequests\OutreachRequestResource;
use App\Models\OutreachRequest;
use App\Services\RequestNotifications\RequestNotificationData;
use App\Services\RequestNotifications\RequestNotificationPresenter;
use Illuminate\Database\Eloquent\Model;

class OutreachRequestNotificationPresenter implements RequestNotificationPresenter
{
    public function present(Model $request): RequestNotificationData
    {
        /** @var OutreachRequest $request */
        return new RequestNotificationData(
            type: 'Заявка на выездной мастер-класс',
            number: (string) $request->number,
            adminUrl: OutreachRequestResource::getUrl('edit', ['record' => $request]),
            fields: [
                $this->field('Дата и время обращения', $this->dateTime($request->created_at)),
                $this->field('Номер заявки', (string) $request->number),
                $this->field('Направление', (string) $request->direction),
                $this->field('Желаемая дата', $this->date($request->requested_date)),
                $this->field('Место проведения', (string) $request->location),
                $this->field('Расстояние', $request->distance_km !== null ? $request->distance_km.' км' : '—'),
                $this->field('Количество участников', (string) $request->participants_count),
                $this->field('Возрастная группа', (string) ($request->age_group ?: '—')),
                $this->field('Расчётная стоимость', money((int) $request->calc_total_cents, (string) ($request->currency ?? 'RUB'))),
                $this->field('Статус', $request->status?->label() ?? '—'),
                $this->field('Имя контакта', (string) $request->contact_name),
                $this->field('Телефон', (string) $request->contact_phone),
                $this->field('Email', (string) $request->contact_email),
                $this->field('Комментарий', (string) ($request->comment ?: '—')),
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

    private function date(mixed $value): string
    {
        return $value?->format('d.m.Y') ?? '—';
    }
}
