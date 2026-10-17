<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('course_module_id')->label('Модуль')->relationship('module', 'title')->searchable()->preload()->required(),
            TextInput::make('title')->label('Название урока')->required(),
            TextInput::make('slug')->label('Слаг')->required(),
            TextInput::make('duration_minutes')->label('Длительность, мин')->numeric()->default(0),
            TextInput::make('sort')->label('Порядок')->numeric()->default(0),
            Toggle::make('is_preview')->label('Бесплатный (preview)')->default(false),
            Toggle::make('is_published')->label('Опубликован')->default(true),
            Textarea::make('content')->label('Текст урока')->rows(5)->columnSpanFull(),
            TextInput::make('video_path')->label('Путь к видео (приватное хранилище)')->columnSpanFull()->helperText('Платные материалы не размещаются в публичной папке.'),
        ])->columns(2);
    }
}
