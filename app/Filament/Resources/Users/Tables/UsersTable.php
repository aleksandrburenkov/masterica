<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Имя')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->copyable(),
                TextColumn::make('phone')->label('Телефон')->toggleable(),
                TextColumn::make('role')->label('Роль')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('bookings_count')->label('Записи')->counts('bookings'),
                TextColumn::make('orders_count')->label('Заказы')->counts('orders'),
                TextColumn::make('created_at')->label('Регистрация')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')->label('Роль')->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
