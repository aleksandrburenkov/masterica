<?php

namespace App\Filament\Resources\DistanceTariffs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DistanceTariffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Интервал и наценка')
                ->description('Расстояние считается от офиса студии (координаты задаются в «Настройках студии»). Пока расстояние меньше значения «От» первого тарифа, наценки нет — так город остаётся без надбавки.')
                ->schema([
                    TextInput::make('min_distance')
                        ->label('От, км')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->required()
                        ->suffix('км'),
                    TextInput::make('max_distance')
                        ->label('До, км')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->nullable()
                        ->helperText('Оставьте пустым, чтобы тариф действовал на всё, что дальше «От».')
                        ->suffix('км'),
                    TextInput::make('surcharge_per_participant')
                        ->label('Наценка на участника, ₽')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->required()
                        ->suffix('₽'),
                ])
                ->columns(3),
        ]);
    }
}
