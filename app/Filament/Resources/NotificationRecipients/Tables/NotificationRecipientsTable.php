<?php

namespace App\Filament\Resources\NotificationRecipients\Tables;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationRecipientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Пользователь')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email')->searchable()->copyable(),
                TextColumn::make('user.role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('created_at')->label('Добавлен')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([DeleteAction::make()->label('Удалить')]);
    }
}
