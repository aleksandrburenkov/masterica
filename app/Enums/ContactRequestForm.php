<?php

namespace App\Enums;

/**
 * Источник обращения в разделе «Форма: Контакты».
 *
 * Обращения попадают сюда из формы на странице «Контакты» и из формы запроса
 * мастер-класса, у которого нет активных сеансов.
 */
enum ContactRequestForm: string
{
    case Contacts = 'contacts';
    case MasterclassRequest = 'masterclass_request';

    public function label(): string
    {
        return match ($this) {
            self::Contacts => 'Форма «Контакты»',
            self::MasterclassRequest => 'Форма «Запрос мастер-класса» (нет активных сеансов)',
        };
    }
}
