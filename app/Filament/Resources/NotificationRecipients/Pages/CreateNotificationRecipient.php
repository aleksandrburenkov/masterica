<?php

namespace App\Filament\Resources\NotificationRecipients\Pages;

use App\Filament\Resources\NotificationRecipients\NotificationRecipientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationRecipient extends CreateRecord
{
    protected static string $resource = NotificationRecipientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
