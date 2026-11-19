<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\NotificationRecipient;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $this->syncNotificationRecipient();
    }

    private function syncNotificationRecipient(): void
    {
        /** @var User $user */
        $user = $this->record;
        $enabled = (bool) ($this->form->getRawState()['receives_request_notifications'] ?? false);

        if ($enabled && ! $user->isOwner()) {
            NotificationRecipient::query()->firstOrCreate(['user_id' => $user->id]);

            return;
        }

        NotificationRecipient::query()->where('user_id', $user->id)->delete();
    }
}
