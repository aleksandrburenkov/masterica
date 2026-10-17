<?php

namespace App\Filament\Resources\Techniques\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TechniquesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('slug')->label('Слаг')->toggleable(),
                TextColumn::make('masterclasses_count')->label('Занятий')->counts('masterclasses'),
                TextColumn::make('sort')->label('Порядок')->sortable(),
                ToggleColumn::make('is_active')->label('Активно'),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
