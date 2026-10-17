<?php

namespace App\Filament\Resources\OutreachRequests\Schemas;

use App\Enums\OutreachStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OutreachRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Заявка')->schema([
                TextInput::make('number')->label('Номер')->disabled()->dehydrated(),
                TextInput::make('direction')->label('Направление')->required(),
                DatePicker::make('requested_date')->label('Дата')->required(),
                TextInput::make('location')->label('Место')->required(),
                TextInput::make('participants_count')->label('Участников')->numeric()->required(),
                TextInput::make('age_group')->label('Возрастная группа'),
                Select::make('status')->label('Статус')->options(collect(OutreachStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
            ])->columns(2),

            Section::make('Контакт')->schema([
                TextInput::make('contact_name')->label('Имя')->required(),
                TextInput::make('contact_phone')->label('Телефон')->required(),
                TextInput::make('contact_email')->label('Email')->email()->required(),
                Textarea::make('comment')->label('Комментарий')->rows(3)->columnSpanFull(),
            ])->columns(2),

            Section::make('Расчёт')->schema([
                TextInput::make('base_price_cents')->label('Базовая цена, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('included_participants')->label('Включено участников')->numeric(),
                TextInput::make('extra_count')->label('Доп. участников')->numeric(),
                TextInput::make('extra_price_cents')->label('Цена доп. участника, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('calc_total_cents')->label('Итог, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
            ])->columns(2),
        ]);
    }
}
