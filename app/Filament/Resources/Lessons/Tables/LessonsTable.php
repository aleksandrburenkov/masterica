<?php

namespace App\Filament\Resources\Lessons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module.course.title')->label('Курс')->wrap()->toggleable(),
                TextColumn::make('module.title')->label('Модуль')->wrap(),
                TextColumn::make('sort')->label('№')->sortable(),
                TextColumn::make('title')->label('Урок')->searchable()->wrap(),
                TextColumn::make('duration_minutes')->label('Мин'),
                TextColumn::make('is_preview')->label('Free')->badge()->formatStateUsing(fn ($state) => $state ? 'Да' : 'Нет')->color(fn ($state) => $state ? 'info' : 'gray'),
                ToggleColumn::make('is_published')->label('Опубликован'),
            ])
            ->defaultSort('id')
            ->filters([
                SelectFilter::make('course')->label('Курс')->relationship('module.course', 'title'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
