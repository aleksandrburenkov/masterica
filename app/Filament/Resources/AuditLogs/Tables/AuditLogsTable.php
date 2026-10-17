<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Время')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('actor.email')->label('Актор')->toggleable(),
                TextColumn::make('action')->label('Действие')->badge()->searchable(),
                TextColumn::make('auditable_type')->label('Объект')->formatStateUsing(fn ($state) => $state ? class_basename($state).' #'.$state : '—')->toggleable(),
                TextColumn::make('auditable_id')->label('ID')->toggleable(),
                TextColumn::make('reason')->label('Причина')->wrap()->toggleable(),
            ])
            ->defaultSort('id', 'desc');
    }
}
