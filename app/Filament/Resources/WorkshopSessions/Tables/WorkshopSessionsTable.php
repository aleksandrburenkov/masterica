<?php

namespace App\Filament\Resources\WorkshopSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkshopSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')->label('Дата и время')
                    ->formatStateUsing(fn ($state, $record) => $record->starts_at->copy()->setTimezone($record->timezone)->translatedFormat('d.m.Y H:i'))
                    ->sortable(),
                TextColumn::make('masterclass.title')->label('Мастер-класс')->searchable()->wrap(),
                TextColumn::make('master.name')->label('Ведущий')->toggleable(),
                TextColumn::make('capacity')->label('Мест')->sortable(),
                TextColumn::make('seats_left')->label('Свободно')->state(fn ($record) => $record->seatsLeft())->badge()->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('status')->label('Статус')->badge(),
                ToggleColumn::make('is_published')->label('Опубликован'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                SelectFilter::make('masterclass')->relationship('masterclass', 'title')->label('Мастер-класс'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
