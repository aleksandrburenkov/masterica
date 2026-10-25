<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Filament\Support\SlugField;
use App\Models\Course;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Курс')->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(SlugField::syncFromSource(Course::class))
                    ->columnSpanFull(),
                SlugField::make(Course::class, helperText: 'Публичный адрес: /kursy/{слаг}. Формируется из названия, при совпадении добавляется номер.'),
                Select::make('master_id')->label('Автор')->relationship('master', 'name')->searchable()->preload(),
                Select::make('technique_id')->label('Техника')->relationship('technique', 'name')->searchable()->preload(),
                Textarea::make('short_description')->label('Краткое описание')->required()->rows(2)->columnSpanFull(),
                Textarea::make('description')->label('Описание')->rows(4)->columnSpanFull(),
                Textarea::make('outcome')->label('Результат обучения')->rows(2)->columnSpanFull(),
            ])->columns(2),

            Section::make('Цена и доступ')->schema([
                Select::make('level')->label('Уровень')->options(['beginner' => 'Начинающий', 'advanced' => 'Продолжающий'])->default('beginner'),
                TextInput::make('price_cents')->label('Цена, ₽')->numeric()->required()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('access_days')->label('Срок доступа, дней')->numeric()->default(365),
                FileUpload::make('image')->label('Обложка')->image()->disk('public')->visibility('public')->directory('courses'),
                Toggle::make('is_published')->label('Опубликован')->default(true),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2),
            ])->columns(2),
        ]);
    }
}
