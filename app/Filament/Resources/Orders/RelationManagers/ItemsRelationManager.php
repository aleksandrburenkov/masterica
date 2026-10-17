<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Состав заказа';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')->label('Товар')->wrap(),
                TextColumn::make('variant_name')->label('Вариант'),
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('unit_price_cents')->label('Цена')->money('RUB', divideBy: 100),
                TextColumn::make('quantity')->label('Кол-во'),
                TextColumn::make('line_total_cents')->label('Сумма')->money('RUB', divideBy: 100),
            ])
            ->paginated(false);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
