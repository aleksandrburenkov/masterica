<?php

namespace App\Filament\Resources\OutreachRequests\Pages;

use App\Filament\Resources\OutreachRequests\OutreachRequestResource;
use App\Filament\Support\Concerns\RefreshesNavigationBadges;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOutreachRequest extends EditRecord
{
    use RefreshesNavigationBadges;

    protected static string $resource = OutreachRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
