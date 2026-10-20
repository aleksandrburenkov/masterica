<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Товар')->schema([
                TextInput::make('title')->label('Название')->required()->columnSpanFull(),
                TextInput::make('slug')->label('Слаг (URL)')->required()->unique(ignoreRecord: true),
                Select::make('technique_id')->label('Техника')->relationship('technique', 'name')->searchable()->preload(),
                TextInput::make('short_description')->label('Краткое описание')->required()->columnSpanFull(),
                Textarea::make('description')->label('Описание')->rows(4)->columnSpanFull(),
                TextInput::make('price_cents')->label('Базовая цена, ₽')->numeric()->required()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                FileUpload::make('image')->label('Изображение')->image()->disk('public')->visibility('public')->directory('products'),
                Toggle::make('is_published')->label('Опубликовано')->default(true),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2),
            ])->columns(2),
        ]);
    }
}
