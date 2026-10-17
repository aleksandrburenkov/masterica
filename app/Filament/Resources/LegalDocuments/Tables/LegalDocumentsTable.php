<?php

namespace App\Filament\Resources\LegalDocuments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Документ')->searchable()->wrap(),
                TextColumn::make('key')->label('Ключ')->badge()->toggleable(),
                TextColumn::make('version')->label('Редакция')->toggleable(),
                TextColumn::make('effective_date')->label('В силе с')->date('d.m.Y')->toggleable(),
                IconColumn::make('is_production_ready')->label('Production')->boolean(),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
