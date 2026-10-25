<?php

namespace App\Filament\Resources\Techniques\Schemas;

use App\Filament\Support\SlugField;
use App\Models\Technique;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TechniqueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(120)
                ->live(onBlur: true)
                ->afterStateUpdated(SlugField::syncFromSource(Technique::class)),
            SlugField::make(Technique::class, helperText: 'Используется в фильтрах каталога. Формируется из названия техники.'),
            Textarea::make('description')->label('Описание')->rows(3)->columnSpanFull(),
            TextInput::make('sort')->label('Порядок')->numeric()->default(0),
            Toggle::make('is_active')->label('Активно')->default(true),
        ])->columns(2);
    }
}
