<?php

namespace App\Filament\Resources\OutreachRequests;

use App\Filament\Resources\OutreachRequests\Pages\CreateOutreachRequest;
use App\Filament\Resources\OutreachRequests\Pages\EditOutreachRequest;
use App\Filament\Resources\OutreachRequests\Pages\ListOutreachRequests;
use App\Filament\Resources\OutreachRequests\Schemas\OutreachRequestForm;
use App\Filament\Resources\OutreachRequests\Tables\OutreachRequestsTable;
use App\Models\OutreachRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OutreachRequestResource extends Resource
{
    protected static ?string $model = OutreachRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки';

    protected static ?string $navigationLabel = 'Выездные мастер-классы';

    protected static ?string $modelLabel = 'Заявка на выездной мастер-класс';

    protected static ?string $pluralModelLabel = 'Выездные мастер-классы';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return OutreachRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutreachRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutreachRequests::route('/'),
            'create' => CreateOutreachRequest::route('/create'),
            'edit' => EditOutreachRequest::route('/{record}/edit'),
        ];
    }
}
