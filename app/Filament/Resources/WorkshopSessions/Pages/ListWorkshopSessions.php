<?php

namespace App\Filament\Resources\WorkshopSessions\Pages;

use App\Filament\Resources\WorkshopSessions\WorkshopSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkshopSessions extends ListRecords
{
    protected static string $resource = WorkshopSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
