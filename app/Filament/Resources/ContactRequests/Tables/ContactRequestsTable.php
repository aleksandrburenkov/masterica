<?php

namespace App\Filament\Resources\ContactRequests\Tables;

use App\Enums\OutreachStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Номер')->searchable(),
                TextColumn::make('contact_name')->label('Имя')->searchable(),
                TextColumn::make('contact_phone')->label('Телефон'),
                TextColumn::make('contact_email')->label('Email')->toggleable(),
                TextColumn::make('message')->label('Сообщение')->limit(60)->wrap(),
                TextColumn::make('status')->label('Статус')->badge()->formatStateUsing(fn ($state) => $state->label()),
                IconColumn::make('consent_marketing')->label('Рассылка')->boolean()->toggleable(),
                TextColumn::make('source')->label('Источник')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Получено')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(collect(OutreachStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
