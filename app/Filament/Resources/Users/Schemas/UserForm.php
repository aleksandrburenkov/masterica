<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Toggle::make('consent_marketing')->label('Согласие на рассылку'),
                Toggle::make('receives_request_notifications')
                    ->label('Получать письма о новых обращениях')
                    ->helperText('Письма будут приходить на email, указанный выше. Владелец получает их автоматически.')
                    ->dehydrated(false)
                    ->visible(fn (): bool => auth()->user()?->isOwner() ?? false)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
