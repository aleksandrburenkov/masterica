<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Заказ')->schema([
                TextInput::make('number')->label('Номер')->disabled()->dehydrated(),
                Select::make('user_id')->label('Клиент')->relationship('user', 'email')->searchable()->preload(),
                Select::make('status')->label('Статус')->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
                Select::make('payment_status')->label('Оплата')->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
                Select::make('delivery_method')->label('Получение')->options(['pickup' => 'Самовывоз', 'city_delivery' => 'Доставка по городу'])->required(),
                TextInput::make('address')->label('Адрес')->columnSpanFull(),
                TextInput::make('subtotal_cents')->label('Товары, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('delivery_cents')->label('Доставка, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('total_cents')->label('Итого, ₽')->numeric()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
            ])->columns(2),

            Section::make('Контакт')->schema([
                TextInput::make('contact_name')->label('Имя'),
                TextInput::make('contact_phone')->label('Телефон'),
                TextInput::make('contact_email')->label('Email')->email(),
                Textarea::make('comment')->label('Комментарий')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
