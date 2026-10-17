<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Master = 'master';
    case Manager = 'manager';
    case Owner = 'owner';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Клиент',
            self::Master => 'Мастер',
            self::Manager => 'Менеджер',
            self::Owner => 'Владелец',
        };
    }

    /** Staff roles may access the Filament admin panel. */
    public function isStaff(): bool
    {
        return $this !== self::Client;
    }

    public function canManageSettings(): bool
    {
        return $this === self::Owner;
    }
}
