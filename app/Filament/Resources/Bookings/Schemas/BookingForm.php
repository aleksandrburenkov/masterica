<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Запись')->schema([
                TextInput::make('number')->label('Номер')->disabled()->dehydrated(),
                Select::make('user_id')->label('Пользователь')->relationship('user', 'email')->searchable()->preload()->nullable()->placeholder('Гость (без аккаунта)'),
                Select::make('workshop_session_id')->label('Сеанс')->relationship('session', 'id')->getOptionLabelFromRecordUsing(fn ($record) => $record->masterclass->title.' — '.$record->starts_at->format('d.m.Y H:i'))->searchable()->preload()->required(),
                Select::make('status')->label('Статус записи')->options(self::statusOptions())->required(),
                Select::make('payment_status')->label('Статус оплаты')->options(self::paymentOptions())->required(),
                TextInput::make('participants_count')->label('Участников')->numeric()->minValue(1)->required(),
                TextInput::make('child_age')->label('Возраст ребёнка')->numeric(),
            ])->columns(2),

            Section::make('Контакт взрослого')->schema([
                TextInput::make('contact_name')->label('Имя')->required(),
                TextInput::make('contact_phone')->label('Телефон')->required(),
                TextInput::make('contact_email')->label('Email')->email()->required(),
                TextInput::make('total_cents')->label('Сумма, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                Textarea::make('notes')->label('Заметки')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return collect(BookingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    }

    /** @return array<string, string> */
    public static function paymentOptions(): array
    {
        return collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    }
}
