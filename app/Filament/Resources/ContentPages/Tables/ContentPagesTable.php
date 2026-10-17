<?php

namespace App\Filament\Resources\ContentPages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ContentPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Заголовок')->searchable()->wrap(),
                TextColumn::make('slug')->label('Слаг')->toggleable(),
                TextColumn::make('type')->label('Тип')->badge(),
                TextColumn::make('published_at')->label('Опубликовано')->dateTime('d.m.Y')->toggleable(),
                ToggleColumn::make('is_published')->label('Опубликовано'),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
