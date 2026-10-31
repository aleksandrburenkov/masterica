<?php

namespace App\Filament\Support;

use App\Models\ProductVariant;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

/**
 * Единое поле артикула (SKU) для ресурсов админ-панели.
 *
 * Поведение совпадает с полем слага:
 *  - артикул подставляется из исходного поля (обычно «Название») при потере
 *    фокуса, только если поле ещё пустое;
 *  - ручной ввод нормализуется: транслитерация, верхний регистр, дефисы;
 *  - уникальность проверяется и в собственной таблице, и в остатках склада,
 *    чтобы артикул не повторялся в системе.
 */
final class SkuField
{
    public static function make(string $model, ?string $helperText = null): TextInput
    {
        $maxLength = $model::skuMaxLength();

        return TextInput::make('sku')
            ->label('Артикул (SKU)')
            ->helperText($helperText ?? 'Формируется автоматически из названия. При совпадении добавляется номер: -2, -3. Артикул уникален.')
            ->required()
            ->maxLength($maxLength)
            ->trim()
            ->live(onBlur: true)
            ->rule('regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/')
            ->unique(ignoreRecord: true)
            ->rule(fn (?Model $record): Closure => self::warehouseCollisionRule($record))
            ->afterStateUpdated(function (?string $state, Set $set) use ($model, $maxLength): void {
                if ($state === null || trim($state) === '') {
                    return;
                }

                $set('sku', $model::normalizeSku($state, $maxLength));
            })
            ->validationMessages([
                'regex' => 'Артикул может содержать только заглавные латинские буквы, цифры и дефисы. Кириллица транслитерируется автоматически.',
                'unique' => 'Такой артикул уже используется. Укажите другой.',
            ]);
    }

    /**
     * Обработчик для исходного поля: заполняет артикул, пока пользователь его
     * не изменил.
     */
    public static function syncFromSource(string $model): Closure
    {
        return function (?string $state, Get $get, Set $set, ?Model $record) use ($model): void {
            if (filled($get('sku'))) {
                return;
            }

            $set('sku', $model::uniqueSku((string) $state, $record?->getKey()));
        };
    }

    private static function warehouseCollisionRule(?Model $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            if (blank($value)) {
                return;
            }

            $exists = ProductVariant::query()
                ->where('sku', $value)
                ->when($record !== null, fn ($query) => $query->where('product_id', '!=', $record->getKey()))
                ->exists();

            if ($exists) {
                $fail('Такой артикул уже используется в остатках склада.');
            }
        };
    }
}
