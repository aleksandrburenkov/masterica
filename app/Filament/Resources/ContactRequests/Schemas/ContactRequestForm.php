<?php

namespace App\Filament\Resources\ContactRequests\Schemas;

use App\Enums\OutreachStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Обращение')->schema([
                TextInput::make('number')->label('Номер')->disabled()->dehydrated(),
                Select::make('status')->label('Статус')->options(collect(OutreachStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required()->default(OutreachStatus::New->value),
                TextInput::make('contact_name')->label('Имя')->required(),
                TextInput::make('contact_phone')->label('Телефон')->required(),
                TextInput::make('contact_email')->label('Email')->email()->required(),
                TextInput::make('source')->label('Источник'),
                Textarea::make('message')->label('Сообщение')->rows(5)->required()->columnSpanFull(),
            ])->columns(2),

            Section::make('Согласия')->schema([
                Toggle::make('consent_data')->label('Согласие на обработку персональных данных')->disabled()->dehydrated(),
                Toggle::make('consent_marketing')->label('Согласие на рассылку')->disabled()->dehydrated(),
            ])->columns(2),
        ]);
    }
}
