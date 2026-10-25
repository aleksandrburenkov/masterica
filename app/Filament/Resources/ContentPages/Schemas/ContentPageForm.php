<?php

namespace App\Filament\Resources\ContentPages\Schemas;

use App\Filament\Support\SlugField;
use App\Models\ContentPage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Страница')->schema([
                TextInput::make('title')
                    ->label('Заголовок')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(SlugField::syncFromSource(ContentPage::class))
                    ->columnSpanFull(),
                SlugField::make(ContentPage::class, helperText: 'Публичный адрес: /materialy/{слаг}. Формируется из заголовка, при совпадении добавляется номер.'),
                TextInput::make('type')->label('Тип')->default('guide'),
                Textarea::make('excerpt')->label('Краткое описание')->rows(2)->columnSpanFull(),
                Textarea::make('body')->label('Содержимое')->rows(8)->columnSpanFull(),
                FileUpload::make('image')->label('Изображение')->image()->disk('public')->visibility('public')->directory('content'),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
                Toggle::make('is_published')->label('Опубликовано')->default(true),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2),
            ])->columns(2),
        ]);
    }
}
