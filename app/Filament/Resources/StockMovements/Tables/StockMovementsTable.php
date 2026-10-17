<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('variant.product.title')->label('Товар')->wrap(),
                TextColumn::make('variant.sku')->label('SKU')->toggleable(),
                TextColumn::make('type')->label('Тип')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('quantity')->label('Кол-во')->badge()->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('reason')->label('Причина')->wrap()->toggleable(),
                TextColumn::make('actor.email')->label('Актор')->toggleable(),
                TextColumn::make('reference_id')->label('Документ')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')->label('Тип')->options([
                    'receipt' => 'Поступление',
                    'reserve' => 'Резерв',
                    'release' => 'Освобождение',
                    'writeoff' => 'Списание',
                    'adjustment' => 'Корректировка',
                ]),
            ]);
    }
}
