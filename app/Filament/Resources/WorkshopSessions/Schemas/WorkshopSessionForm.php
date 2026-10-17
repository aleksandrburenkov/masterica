<?php

namespace App\Filament\Resources\WorkshopSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkshopSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Сеанс')->schema([
                Select::make('masterclass_id')->label('Мастер-класс')->relationship('masterclass', 'title')->searchable()->preload()->required(),
                Select::make('master_id')->label('Ведущий мастер')->relationship('master', 'name')->searchable()->preload(),
                DateTimePicker::make('starts_at')->label('Дата и время (локально)')->timezone('Europe/Moscow')->seconds(false)->required(),
                TextInput::make('timezone')->label('Часовой пояс')->default('Europe/Moscow')->required(),
                TextInput::make('capacity')->label('Вместимость')->numeric()->default(8)->required(),
                TextInput::make('price_cents')
                    ->label('Цена за участника, ₽ (0 = из мастер-класса)')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                    ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
                TextInput::make('location_name')->label('Место'),
                TextInput::make('address')->label('Адрес')->columnSpanFull(),
                Select::make('status')->label('Статус')->options(['scheduled' => 'Запланирован', 'cancelled' => 'Отменён'])->default('scheduled'),
                Toggle::make('is_published')->label('Опубликован')->default(true),
            ])->columns(2),
        ]);
    }
}
