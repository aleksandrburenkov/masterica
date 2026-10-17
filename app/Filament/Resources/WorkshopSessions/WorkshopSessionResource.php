<?php

namespace App\Filament\Resources\WorkshopSessions;

use App\Filament\Resources\WorkshopSessions\Pages\CreateWorkshopSession;
use App\Filament\Resources\WorkshopSessions\Pages\EditWorkshopSession;
use App\Filament\Resources\WorkshopSessions\Pages\ListWorkshopSessions;
use App\Filament\Resources\WorkshopSessions\Schemas\WorkshopSessionForm;
use App\Filament\Resources\WorkshopSessions\Tables\WorkshopSessionsTable;
use App\Models\WorkshopSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkshopSessionResource extends Resource
{
    protected static ?string $model = WorkshopSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Расписание';

    protected static ?string $modelLabel = 'Сеанс';

    protected static ?string $pluralModelLabel = 'Расписание сеансов';

    public static function form(Schema $schema): Schema
    {
        return WorkshopSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkshopSessionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkshopSessions::route('/'),
            'create' => CreateWorkshopSession::route('/create'),
            'edit' => EditWorkshopSession::route('/{record}/edit'),
        ];
    }
}
