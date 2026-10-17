<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use App\Enums\StockMovementType;
use App\Models\ProductVariant;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.title')->label('Товар')->searchable()->wrap(),
                TextColumn::make('name')->label('Вариант')->searchable(),
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('price_cents')->label('Цена')->money('RUB', divideBy: 100),
                TextColumn::make('stock')->label('Остаток')->badge()->color(fn ($state) => $state > 0 ? 'success' : 'danger')->sortable(),
                TextColumn::make('reserved')->label('В заказах')->state(fn (ProductVariant $record) => app(StockService::class)->reservedFor($record)),
                ToggleColumn::make('is_active')->label('Активен'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('product')->relationship('product', 'title')->label('Товар'),
            ])
            ->recordActions([
                Action::make('receipt')
                    ->label('Поступление')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->schema([TextInput::make('quantity')->label('Количество')->numeric()->required()->minValue(1)])
                    ->action(fn (ProductVariant $record, array $data) => app(StockService::class)->record($record, StockMovementType::Receipt, (int) $data['quantity'], 'Поступление (админ)', auth()->user())),
                Action::make('adjust')
                    ->label('Корректировка')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([TextInput::make('quantity')->label('Изменение (+/−)')->numeric()->required()->helperText('Компенсирующая запись с причиной.')])
                    ->action(fn (ProductVariant $record, array $data) => app(StockService::class)->record($record, StockMovementType::Adjustment, (int) $data['quantity'], 'Корректировка (админ)', auth()->user())),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
