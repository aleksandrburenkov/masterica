<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')->label('Товар')->relationship('product', 'title')->searchable()->preload()->required(),
            TextInput::make('name')->label('Название варианта')->required(),
            TextInput::make('sku')->label('Артикул (SKU)')->required()->unique(ignoreRecord: true),
            TextInput::make('price_cents')->label('Цена, ₽')->numeric()->required()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
            TextInput::make('stock')->label('Остаток (тек.)')->numeric()->disabled()->dehydrated(false)->helperText('Остаток меняется движениями склада, а не ручным редактированием.'),
            Toggle::make('is_active')->label('Активен')->default(true),
        ])->columns(2);
    }
}
