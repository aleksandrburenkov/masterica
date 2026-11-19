<?php

namespace App\Services\RequestNotifications\Presenters;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\RequestNotifications\RequestNotificationData;
use App\Services\RequestNotifications\RequestNotificationPresenter;
use Illuminate\Database\Eloquent\Model;

class OrderRequestNotificationPresenter implements RequestNotificationPresenter
{
    public function present(Model $request): RequestNotificationData
    {
        /** @var Order $request */
        $request->loadMissing('items');

        return new RequestNotificationData(
            type: 'Заказ из магазина',
            number: (string) $request->number,
            adminUrl: OrderResource::getUrl('edit', ['record' => $request]),
            fields: [
                $this->field('Дата и время заказа', $this->dateTime($request->placed_at ?? $request->created_at)),
                $this->field('Номер заказа', (string) $request->number),
                $this->field('Статус заказа', $request->status?->label() ?? '—'),
                $this->field('Статус оплаты', $request->payment_status?->label() ?? '—'),
                $this->field('Имя покупателя', (string) $request->contact_name),
                $this->field('Телефон', (string) $request->contact_phone),
                $this->field('Email', (string) $request->contact_email),
                $this->field('Способ получения', $request->delivery_method?->label() ?? '—'),
                $this->field('Адрес доставки', (string) ($request->address ?: '—')),
                $this->field('Состав заказа', $this->items($request)),
                $this->field('Товары', money((int) $request->subtotal_cents, (string) ($request->currency ?? 'RUB'))),
                $this->field('Доставка', money((int) $request->delivery_cents, (string) ($request->currency ?? 'RUB'))),
                $this->field('Итого', money((int) $request->total_cents, (string) ($request->currency ?? 'RUB'))),
                $this->field('Комментарий', (string) ($request->comment ?: '—')),
                $this->field('Согласие на рассылку', $request->consent_marketing ? 'Да' : 'Нет'),
            ],
        );
    }

    private function items(Order $order): string
    {
        if ($order->items->isEmpty()) {
            return '—';
        }

        return $order->items
            ->map(fn ($item): string => sprintf(
                '%s — %s, %d шт. × %s = %s',
                $item->product_name,
                $item->variant_name,
                $item->quantity,
                money((int) $item->unit_price_cents, (string) ($item->currency ?? 'RUB')),
                money((int) $item->line_total_cents, (string) ($item->currency ?? 'RUB')),
            ))
            ->implode("\n");
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
