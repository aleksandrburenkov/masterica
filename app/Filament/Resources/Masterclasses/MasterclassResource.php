<?php

namespace App\Filament\Resources\Masterclasses;

use App\Filament\Resources\Masterclasses\Pages\CreateMasterclass;
use App\Filament\Resources\Masterclasses\Pages\EditMasterclass;
use App\Filament\Resources\Masterclasses\Pages\ListMasterclasses;
use App\Filament\Resources\Masterclasses\Schemas\MasterclassForm;
use App\Filament\Resources\Masterclasses\Tables\MasterclassesTable;
use App\Models\Masterclass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MasterclassResource extends Resource
{
    protected static ?string $model = Masterclass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MasterclassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterclassesTable::configure($table);
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
            'index' => ListMasterclasses::route('/'),
            'create' => CreateMasterclass::route('/create'),
            'edit' => EditMasterclass::route('/{record}/edit'),
        ];
    }
}
