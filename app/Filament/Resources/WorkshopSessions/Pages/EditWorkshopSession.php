<?php

namespace App\Filament\Resources\WorkshopSessions\Pages;

use App\Filament\Resources\WorkshopSessions\WorkshopSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkshopSession extends EditRecord
{
    protected static string $resource = WorkshopSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
