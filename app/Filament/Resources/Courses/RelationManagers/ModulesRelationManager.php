<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $title = 'Модули';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Название модуля')->required()->columnSpanFull(),
            Textarea::make('description')->label('Описание')->rows(2)->columnSpanFull(),
            TextInput::make('sort')->label('Порядок')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort')->label('№')->sortable(),
                TextColumn::make('title')->label('Модуль')->wrap(),
                TextColumn::make('lessons_count')->label('Уроков')->counts('lessons'),
            ])
            ->defaultSort('sort')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
