<?php

namespace App\Filament\Resources\DistanceTariffs;

use App\Filament\Resources\DistanceTariffs\Pages\CreateDistanceTariff;
use App\Filament\Resources\DistanceTariffs\Pages\EditDistanceTariff;
use App\Filament\Resources\DistanceTariffs\Pages\ListDistanceTariffs;
use App\Filament\Resources\DistanceTariffs\Schemas\DistanceTariffForm;
use App\Filament\Resources\DistanceTariffs\Tables\DistanceTariffsTable;
use App\Models\DistanceTariff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DistanceTariffResource extends Resource
{
    protected static ?string $model = DistanceTariff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Тарифы удалённости';

    protected static ?string $modelLabel = 'Тариф удалённости';

    protected static ?string $pluralModelLabel = 'Тарифы удалённости';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return DistanceTariffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DistanceTariffsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDistanceTariffs::route('/'),
            'create' => CreateDistanceTariff::route('/create'),
            'edit' => EditDistanceTariff::route('/{record}/edit'),
        ];
    }
}
