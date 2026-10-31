<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\SkuField;
use App\Filament\Support\SlugField;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основная информация')->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(SlugField::syncFromSource(Product::class))
                    ->afterStateUpdated(SkuField::syncFromSource(Product::class))
                    ->columnSpanFull(),
                SlugField::make(Product::class, helperText: 'Публичный адрес: /magazin/{слаг}. Формируется из названия, при совпадении добавляется номер.'),
                SkuField::make(Product::class),
                Select::make('technique_id')->label('Техника')->relationship('technique', 'name')->searchable()->preload(),
                TextInput::make('sizes')->label('Размеры товара')->maxLength(255)->helperText('Например: 25×20 см. Необязательно.'),
                TextInput::make('short_description')->label('Краткое описание')->required()->columnSpanFull(),
                Textarea::make('description')->label('Описание')->rows(4)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Цена и публикация')->schema([
                TextInput::make('price_cents')->label('Базовая цена, ₽')->numeric()->required()->formatStateUsing(fn ($state) => $state ? $state / 100 : null)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('sale_price_cents')
                    ->label('Акционная цена, ₽')
                    ->numeric()
                    ->lte('price_cents')
                    ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                    ->dehydrateStateUsing(fn ($state) => $state === null || trim((string) $state) === '' ? null : (int) round(((float) $state) * 100))
                    ->helperText('Если заполнена, базовая цена в карточке зачёркивается, а акционная выводится как основная.')
                    ->validationMessages(['lte' => 'Акционная цена не может быть выше базовой.']),
                TextInput::make('initial_stock')
                    ->label('Начальный приход')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->visibleOn('create')
                    ->helperText('Количество на складе при создании. Дальше остаток меняется движениями склада.'),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
                Toggle::make('is_published')->label('Опубликовано')->default(true),
            ])->columns(3)->columnSpanFull(),
            Section::make('Изображения')->schema([
                FileUpload::make('image')->label('Изображение')->image()->imagePreviewHeight('200')->disk('public')->visibility('public')->directory('products'),
                FileUpload::make('gallery')
                    ->label(new HtmlString('Карусель изображений товара <span style="color: #8b93a1; font-weight: 400;">(макс. 6 фото)</span>'))
                    ->image()
                    ->imagePreviewHeight('200')
                    ->multiple()
                    ->reorderable()
                    ->maxFiles(6)
                    ->validationMessages([
                        'max' => 'Превышен лимит загрузок фото',
                    ])
                    ->disk('public')
                    ->visibility('public')
                    ->directory('products/gallery'),
            ])->columns(2)->columnSpanFull(),
            Section::make('SEO')->schema([
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
