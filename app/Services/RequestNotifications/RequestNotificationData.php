<?php

namespace App\Services\RequestNotifications;

/**
 * Готовые данные для письма администратору о новом обращении.
 * Презентеры моделей заполняют только этот объект, а отправка письма
 * и определение получателей выполняются единым сервисом.
 */
final readonly class RequestNotificationData
{
    /**
     * @param  array<int, array{label: string, value: string}>  $fields
     */
    public function __construct(
        public string $type,
        public string $number,
        public array $fields,
        public string $adminUrl,
    ) {}

    public function subject(): string
    {
        return 'Новое обращение: '.$this->type.' №'.$this->number;
    }
}
