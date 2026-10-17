<?php

namespace App\Filament\Resources\Masterclasses\Schemas;

use Filament\Forms\Components\FileUpload;
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
                TextInput::make('title')->label('Название')->required()->maxLength(200)->columnSpanFull(),
                TextInput::make('slug')->label('Слаг (URL)')->required()->unique(ignoreRecord: true),
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
                TextInput::make('price_cents')
                    ->label('Цена, ₽')
                    ->numeric()
                    ->required()
                    ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                Toggle::make('materials_included')->label('Материалы включены')->default(true),
                Toggle::make('is_published')->label('Опубликовано')->default(true),
            ])->columns(2),

            Section::make('Медиа и SEO')->schema([
                FileUpload::make('image')->label('Изображение')->image()->directory('masterclasses'),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
            ])->columns(2),
        ]);
    }
}
