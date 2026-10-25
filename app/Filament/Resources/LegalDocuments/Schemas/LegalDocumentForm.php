<?php

namespace App\Filament\Resources\LegalDocuments\Schemas;

use App\Filament\Support\SlugField;
use App\Models\LegalDocument;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Форма правового документа. Текст хранится в базе и не хардкодится в шаблонах.
 * Технический ключ после создания неизменяем: по нему шаблоны находят документ
 * (например, legal('privacy_policy')), поэтому его случайное переименование
 * сломало бы вывод на сайте.
 */
class LegalDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Документ')->schema([
                TextInput::make('key')
                    ->label('Технический ключ')
                    ->helperText('Не изменяется после создания. По ключу документ находят шаблоны, например legal(\'privacy_policy\').')
                    ->required()
                    ->maxLength(64)
                    ->trim()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated()
                    ->validationMessages([
                        'unique' => 'Документ с таким техническим ключом уже существует. Откройте существующий документ для редактирования или укажите другой ключ.',
                    ]),
                SlugField::make(
                    LegalDocument::class,
                    helperText: 'Публичный адрес документа: /dokumenty/{слаг}. Формируется из заголовка; укажите только последний сегмент, без «/».',
                    normalizeOnBlur: false,
                ),
                TextInput::make('title')
                    ->label('Заголовок')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(SlugField::syncFromSource(LegalDocument::class))
                    ->columnSpanFull(),
                Textarea::make('lead')
                    ->label('Краткая аннотация')
                    ->helperText('Показывается в футере и как подпись согласия у чекбоксов.')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->label('Полный текст (HTML)')
                    ->helperText('Используйте теги <h2>, <p>, <ul>, <li>. Текст выводится на странице документа.')
                    ->rows(16)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Версия и статус')->schema([
                TextInput::make('version')->label('Редакция')->maxLength(32),
                DatePicker::make('effective_date')->label('Дата вступления в силу'),
                Toggle::make('is_production_ready')
                    ->label('Готов к использованию в production')
                    ->helperText('Подтверждает, что текст является юридически значимым, а не черновиком.')
                    ->default(true),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
            ])->columns(2),
        ]);
    }
}
