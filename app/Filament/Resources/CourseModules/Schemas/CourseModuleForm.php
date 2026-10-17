<?php

namespace App\Filament\Resources\CourseModules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('course_id')->label('Курс')->relationship('course', 'title')->searchable()->preload()->required(),
            TextInput::make('title')->label('Название модуля')->required()->columnSpanFull(),
            Textarea::make('description')->label('Описание')->rows(3)->columnSpanFull(),
            TextInput::make('sort')->label('Порядок')->numeric()->default(0),
        ])->columns(2);
    }
}
