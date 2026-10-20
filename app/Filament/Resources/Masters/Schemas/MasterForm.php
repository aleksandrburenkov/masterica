<?php

namespace App\Filament\Resources\Masters\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Профиль')->schema([
                TextInput::make('name')->label('Имя')->required()->maxLength(160),
                TextInput::make('title')->label('Должность / заголовок')->maxLength(200),
                TextInput::make('specialization')->label('Основное направление')->maxLength(200),
                Select::make('user_id')->label('Связанный аккаунт')->relationship('user', 'email')->searchable()->preload(),
                Textarea::make('bio')->label('Биография')->rows(4)->columnSpanFull(),
                TextInput::make('email')->label('Email')->email(),
                TextInput::make('phone')->label('Телефон')->tel(),
                Select::make('techniques')->label('Направления (техники)')->relationship('techniques', 'name')->multiple()->preload(),
                FileUpload::make('photo')->label('Фото')->image()->disk('public')->visibility('public')->directory('masters'),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
                Toggle::make('is_active')->label('Активен')->default(true),
            ])->columns(2),
        ]);
    }
}
