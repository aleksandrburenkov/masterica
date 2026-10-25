<?php

namespace App\Filament\Resources\OutreachRequests\Schemas;

use App\Enums\OutreachStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('distance_km')->label('Расстояние от офиса, км')->numeric()->suffix('км'),
                TextInput::make('participants_count')->label('Участников')->numeric()->required(),
                TextInput::make('age_group')->label('Возрастная группа'),
                Select::make('status')->label('Статус')->options(collect(OutreachStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required()->default(OutreachStatus::New->value),
            ])->columns(2),

            Section::make('Контакт')->schema([
                TextInput::make('contact_name')->label('Имя')->required(),
                TextInput::make('contact_phone')->label('Телефон')->required(),
                TextInput::make('contact_email')->label('Email')->email()->required(),
                Textarea::make('comment')->label('Комментарий')->rows(3)->columnSpanFull(),
                TextInput::make('source')->label('Источник')->helperText('Страница, с которой отправлена форма.'),
            ])->columns(2),

            Section::make('Согласия')->schema([
                Toggle::make('consent_data')->label('Согласие на обработку персональных данных')->disabled()->dehydrated(),
                Toggle::make('consent_marketing')->label('Согласие на рассылку')->disabled()->dehydrated(),
            ])->columns(2),

            Section::make('Расчёт')->schema([
                TextInput::make('base_price_cents')->default(0)->label('Базовая цена, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('included_participants')->default(0)->label('Включено участников')->numeric(),
                TextInput::make('extra_count')->default(0)->label('Доп. участников')->numeric(),
                TextInput::make('extra_price_cents')->default(0)->label('Надбавка за доп. участников, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('distance_surcharge_cents')->default(0)->label('Надбавка за удалённость, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('calc_total_cents')->default(0)->label('Итог, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
            ])->columns(2),
        ]);
    }
}
