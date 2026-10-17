<?php

namespace App\Filament\Resources\OutreachRequests\Pages;

use App\Filament\Resources\OutreachRequests\OutreachRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutreachRequests extends ListRecords
{
    protected static string $resource = OutreachRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
