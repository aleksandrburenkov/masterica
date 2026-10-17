<?php

namespace App\Filament\Resources\Masters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class MastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Имя')->searchable()->sortable(),
                TextColumn::make('specialization')->label('Направление')->toggleable(),
                TextColumn::make('sessions_count')->label('Сеансов')->counts('sessions'),
                TextColumn::make('sort')->label('Порядок')->sortable()->toggleable(),
                ToggleColumn::make('is_active')->label('Активен'),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
