<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use App\Support\NumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateContactRequest extends CreateRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = ContactRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['number'] = NumberGenerator::contactRequest();

        return $data;
    }
}
