<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/**
 * Единое поле слага для всех ресурсов админ-панели.
 *
 * Поведение одинаково во всех формах:
 *  - слаг подставляется из исходного поля (обычно «Название») при потере фокуса,
 *    только если поле ещё пустое, — существующий URL при редактировании не меняется;
 *  - ручной ввод нормализуется: транслитерация, нижний регистр, дефисы;
 *  - уникальность проверяется с учётом области (например, модуля курса).
 */
final class SlugField
{
    /**
     * @param  class-string<Model>  $model  модель с трейтом HasSlug
     * @param  array<string, string>  $scopeFields  колонка БД => имя поля формы
     */
    public static function make(
        string $model,
        array $scopeFields = [],
        ?string $helperText = null,
        bool $normalizeOnBlur = true,
    ): TextInput {
        $maxLength = $model::slugMaxLength();

        return TextInput::make('slug')
            ->label('Слаг (URL)')
            ->helperText($helperText ?? 'Формируется автоматически из названия. При совпадении добавляется номер: -2, -3. Кириллица транслитерируется.')
            ->required()
            ->maxLength($maxLength)
            ->trim()
            ->live(onBlur: true)
            ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get) use ($scopeFields): Unique {
                foreach ($scopeFields as $column => $formField) {
                    $value = $get($formField);

                    if ($value !== null && $value !== '') {
                        $rule->where($column, $value);
                    }
                }

                return $rule;
            })
            ->afterStateUpdated(function (?string $state, Set $set) use ($model, $maxLength, $normalizeOnBlur): void {
                if (! $normalizeOnBlur || $state === null || trim($state) === '') {
                    return;
                }

                $set('slug', $model::normalizeSlug($state, $maxLength));
            })
            ->validationMessages([
                'regex' => 'Слаг может содержать только строчные латинские буквы, цифры и дефисы. Кириллица транслитерируется автоматически.',
                'unique' => 'Такое значение слага уже используется. Укажите другое.',
            ]);
    }

    /**
     * Обработчик для исходного поля: заполняет слаг, пока пользователь его не изменил.
     *
     * @param  class-string<Model>  $model
     * @param  array<string, string>  $scopeFields  колонка БД => имя поля формы
     */
    public static function syncFromSource(string $model, array $scopeFields = []): Closure
    {
        return function (?string $state, Get $get, Set $set, ?Model $record) use ($model, $scopeFields): void {
            if (filled($get('slug'))) {
                return;
            }

            $scope = [];

            foreach ($scopeFields as $column => $formField) {
                $scope[$column] = $get($formField);
            }

            $set('slug', $model::uniqueSlug((string) $state, $record?->getKey(), $scope));
        };
    }
}
