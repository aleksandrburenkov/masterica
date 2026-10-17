<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Отзыв')->schema([
                Select::make('user_id')->label('Автор')->relationship('user', 'email')->searchable()->preload(),
                Select::make('masterclass_id')->label('Мастер-класс')->relationship('masterclass', 'title')->searchable()->preload(),
                Select::make('type')->label('Тип')->options(collect(ReviewType::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
                Select::make('rating')->label('Оценка')->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
                Textarea::make('body')->label('Текст')->rows(5)->required()->columnSpanFull(),
                TextInput::make('video_path')->label('Ссылка на видео')->columnSpanFull(),
                TextInput::make('city')->label('Город'),
                Select::make('status')->label('Статус')->options(collect(ReviewStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
                TextInput::make('source')->label('Источник'),
            ])->columns(2),

            Section::make('Согласия')->schema([
                Toggle::make('consent_publish')->label('Согласие на публикацию')->default(false),
                Toggle::make('consent_marketing')->label('Согласие на маркетинг')->default(false),
                Toggle::make('is_demo')->label('Демонстрационный (demo)')->default(false)->helperText('Демо-отзывы не выдаются за отзывы реальных клиентов и исключаются из production.'),
            ])->columns(3),
        ]);
    }
}
