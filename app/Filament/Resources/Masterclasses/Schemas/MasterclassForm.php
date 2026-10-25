<?php

namespace App\Filament\Resources\Masterclasses\Schemas;

use App\Filament\Support\SlugField;
use App\Models\Masterclass;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterclassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(SlugField::syncFromSource(Masterclass::class))
                    ->columnSpanFull(),
                SlugField::make(Masterclass::class, helperText: 'Публичный адрес: /master-klassy/{слаг}. Формируется из названия, при совпадении добавляется номер.'),
                Select::make('technique_id')->label('Техника')->relationship('technique', 'name')->searchable()->preload(),
                TextInput::make('short_description')->label('Краткое описание')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Описание')->rows(5)->columnSpanFull(),
                Textarea::make('outcome')->label('Результат занятия')->rows(3)->columnSpanFull(),
            ])->columns(2),

            Section::make('Параметры и цена')->schema([
                Select::make('level')->label('Уровень')->options(['beginner' => 'Начинающий', 'advanced' => 'Продолжающий'])->default('beginner'),
                Select::make('format')->label('Формат')->options(['studio' => 'В студии', 'outreach' => 'Выездной'])->default('studio'),
                TextInput::make('age_min')->label('Возраст от')->numeric()->default(12),
                TextInput::make('age_max')->label('Возраст до (необязательно)')->numeric(),
                TextInput::make('duration_minutes')->label('Длительность, мин')->numeric()->default(120),
                self::moneyInput('price_cents', 'Цена в студии, руб.')->required(),
                self::moneyInput('outreach_price_cents', 'Цена выездного мастер-класса, руб. (за человека)'),
                TextInput::make('included_participants')
                    ->label('Количество участников (чел.)')
                    ->numeric()
                    ->minValue(1)
                    ->default(10)
                    ->required(),
                KeyValue::make('participant_surcharges')
                    ->label('% за кол-во человек (чел.)')
                    ->keyLabel('Количество человек')
                    ->valueLabel('Процент, %')
                    ->keyPlaceholder('Например, 3')
                    ->valuePlaceholder('Например, 5')
                    ->helperText('Процент применяется, когда участников больше, чем указано в поле «Количество участников». Ступени: до 3 — 5%, до 5 — 7%.')
                    ->addActionLabel('Добавить')
                    ->columnSpanFull(),
                Toggle::make('materials_included')->label('Материалы включены')->default(true),
                Toggle::make('is_published')->label('Опубликовано')->default(true),
            ])->columns(2),

            Section::make('Медиа и SEO')->schema([
                FileUpload::make('image')->label('Изображение')->image()->disk('public')->visibility('public')->directory('masterclasses'),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    private static function moneyInput(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->inputMode('decimal')
            ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, ',', ' ') : null)
            ->rule('regex:/^\d+(?:[ \x{00A0}]\d{3})*(?:[.,]\d{1,2})?$/u')
            ->validationMessages([
                'regex' => 'Укажите сумму в рублях, например 1 500,00',
            ])
            ->dehydrateStateUsing(function ($state) {
                if ($state === null || trim((string) $state) === '') {
                    return null;
                }

                $value = str_replace([' ', "\u{00A0}"], '', (string) $state);

                return (int) round(((float) str_replace(',', '.', $value)) * 100);
            });
    }
}
