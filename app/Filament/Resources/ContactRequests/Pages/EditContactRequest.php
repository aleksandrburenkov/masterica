<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContactRequest extends EditRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = ContactRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
