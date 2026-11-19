<?php

namespace App\Filament\Resources\NotificationRecipients\Pages;

use App\Filament\Resources\NotificationRecipients\NotificationRecipientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationRecipients extends ListRecords
{
    protected static string $resource = NotificationRecipientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить получателя'),
        ];
    }
}
