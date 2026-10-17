<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Пользователь')->schema([
                TextInput::make('name')->label('Имя')->required()->maxLength(120),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Телефон')->tel(),
                Select::make('role')->label('Роль')->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all())->required()->default('client'),
                TextInput::make('password')->label('Пароль')->password()->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText('Оставьте пустым, чтобы не менять.'),
            ])->columns(2),
        ]);
    }
}
