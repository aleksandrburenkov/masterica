<?php

namespace App\Filament\Resources\CourseModules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')->label('Курс')->wrap()->searchable(),
                TextColumn::make('sort')->label('№')->sortable(),
                TextColumn::make('title')->label('Модуль')->wrap()->searchable(),
                TextColumn::make('lessons_count')->label('Уроков')->counts('lessons'),
            ])
            ->defaultSort('course_id')
            ->filters([
                SelectFilter::make('course')->relationship('course', 'title')->label('Курс'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
