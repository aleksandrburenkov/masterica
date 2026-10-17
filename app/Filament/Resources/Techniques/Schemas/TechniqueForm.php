<?php

namespace App\Filament\Resources\Techniques\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TechniqueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Название')->required()->maxLength(120),
            TextInput::make('slug')->label('Слаг (URL)')->required()->maxLength(120)->unique(ignoreRecord: true),
            Textarea::make('description')->label('Описание')->rows(3)->columnSpanFull(),
            TextInput::make('sort')->label('Порядок')->numeric()->default(0),
            Toggle::make('is_active')->label('Активно')->default(true),
        ])->columns(2);
    }
}
