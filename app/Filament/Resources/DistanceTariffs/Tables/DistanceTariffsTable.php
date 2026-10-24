<?php

namespace App\Filament\Resources\DistanceTariffs\Tables;

use App\Models\DistanceTariff;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DistanceTariffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('interval')
                    ->label('Интервал')
                    ->state(fn (DistanceTariff $record) => $record->intervalLabel()),
                TextColumn::make('surcharge_per_participant')
                    ->label('Наценка на участника')
                    ->suffix(' ₽')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Изменён')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('min_distance')
            ->emptyStateHeading('Тарифы удалённости не заданы')
            ->emptyStateDescription('Добавьте интервалы километража от офиса и наценку за участника — калькулятор на сайте подхватит их автоматически.')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
