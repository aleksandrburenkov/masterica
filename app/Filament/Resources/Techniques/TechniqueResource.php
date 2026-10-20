<?php

namespace App\Filament\Resources\Techniques;

use App\Filament\Resources\Techniques\Pages\CreateTechnique;
use App\Filament\Resources\Techniques\Pages\EditTechnique;
use App\Filament\Resources\Techniques\Pages\ListTechniques;
use App\Filament\Resources\Techniques\Schemas\TechniqueForm;
use App\Filament\Resources\Techniques\Tables\TechniquesTable;
use App\Models\Technique;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TechniqueResource extends Resource
{
    protected static ?string $model = Technique::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Техники исполнения';
    protected static ?string $modelLabel = 'Техника исполнения';
    protected static ?string $pluralModelLabel = 'Техники исполнения';
    public static function form(Schema $schema): Schema
    {
        return TechniqueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TechniquesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTechniques::route('/'),
            'create' => CreateTechnique::route('/create'),
            'edit' => EditTechnique::route('/{record}/edit'),
        ];
    }
}
