<?php

namespace App\Services\RequestNotifications\Presenters;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Models\ContactRequest;
use App\Services\RequestNotifications\RequestNotificationData;
use App\Services\RequestNotifications\RequestNotificationPresenter;
use Illuminate\Database\Eloquent\Model;

class ContactRequestNotificationPresenter implements RequestNotificationPresenter
{
    public function present(Model $request): RequestNotificationData
    {
        /** @var ContactRequest $request */
        return new RequestNotificationData(
            type: $request->isMasterclassRequest() ? 'Запрос мастер-класса' : 'Обращение с сайта',
            number: (string) $request->number,
            adminUrl: ContactRequestResource::getUrl('edit', ['record' => $request]),
            fields: [
                $this->field('Дата и время обращения', $this->dateTime($request->created_at)),
                $this->field('Номер обращения', (string) $request->number),
                $this->field('Тип формы', $request->formSource()->label()),
                $this->field('Статус', $request->status?->label() ?? '—'),
                $this->field('Имя контакта', (string) $request->contact_name),
                $this->field('Телефон', (string) $request->contact_phone),
                $this->field('Email', (string) $request->contact_email),
                $this->field('Содержание обращения', (string) $request->message),
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
}
