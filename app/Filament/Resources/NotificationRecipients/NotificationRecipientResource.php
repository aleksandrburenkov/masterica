<?php

namespace App\Filament\Resources\NotificationRecipients;

use App\Filament\Resources\NotificationRecipients\Pages\CreateNotificationRecipient;
use App\Filament\Resources\NotificationRecipients\Pages\ListNotificationRecipients;
use App\Filament\Resources\NotificationRecipients\Schemas\NotificationRecipientForm;
use App\Filament\Resources\NotificationRecipients\Tables\NotificationRecipientsTable;
use App\Models\NotificationRecipient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Список сотрудников, получающих письма о новых обращениях с сайта.
 * Доступен только владельцу (см. NotificationRecipientPolicy).
 */
class NotificationRecipientResource extends Resource
{
    protected static ?string $model = NotificationRecipient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Рассылка обращений';

    protected static ?string $modelLabel = 'Получатель рассылки';

    protected static ?string $pluralModelLabel = 'Рассылка обращений';

    protected static ?int $navigationSort = 102;

    public static function form(Schema $schema): Schema
    {
        return NotificationRecipientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotificationRecipientsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationRecipients::route('/'),
            'create' => CreateNotificationRecipient::route('/create'),
        ];
    }
}
