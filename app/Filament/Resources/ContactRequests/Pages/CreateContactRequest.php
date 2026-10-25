<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Support\NumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateContactRequest extends CreateRecord
{
    protected static string $resource = ContactRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['number'] = NumberGenerator::contactRequest();

        return $data;
    }
}
