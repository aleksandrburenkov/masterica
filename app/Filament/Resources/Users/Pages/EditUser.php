<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\NotificationRecipient;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['receives_request_notifications'] = NotificationRecipient::query()
            ->where('user_id', $this->record->getKey())
            ->exists();

        return $data;
    }

    protected function afterSave(): void
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
