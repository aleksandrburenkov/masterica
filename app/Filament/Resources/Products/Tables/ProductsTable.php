<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->label('Фото')->height(40)->getStateUsing(fn ($record) => media_url($record->image, 'images/photo-1.jpg')),
                TextColumn::make('title')->label('Название')->searchable()->sortable()->wrap(),
                TextColumn::make('sku')->label('Артикул')->searchable()->copyable(),
                TextColumn::make('technique.name')->label('Техника')->badge(),
                TextColumn::make('price_cents')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('sale_price_cents')->label('Акция')->money('RUB', divideBy: 100)->placeholder('—')->color('danger'),
                TextColumn::make('stock_total')->label('Остаток')->state(fn ($record) => $record->totalStock())->badge()->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('variants_count')->label('Вариантов')->counts('variants'),
                ToggleColumn::make('is_published')->label('Опубликовано'),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
