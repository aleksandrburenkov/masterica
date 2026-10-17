<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable()->wrap(),
                TextColumn::make('master.name')->label('Автор')->toggleable(),
                TextColumn::make('price_cents')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('access_days')->label('Доступ, дн.')->toggleable(),
                TextColumn::make('modules_count')->label('Модулей')->counts('modules'),
                TextColumn::make('enrollments_count')->label('Записались')->counts('enrollments'),
                ToggleColumn::make('is_published')->label('Опубликован'),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
