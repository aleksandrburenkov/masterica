<?php

namespace App\Filament\Resources\OutreachRequests\Pages;

use App\Filament\Resources\OutreachRequests\OutreachRequestResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use App\Support\NumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateOutreachRequest extends CreateRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = OutreachRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['number'] = NumberGenerator::outreach();

        return $data;
    }
}
