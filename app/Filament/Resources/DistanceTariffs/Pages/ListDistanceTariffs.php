<?php

namespace App\Filament\Resources\DistanceTariffs\Pages;

use App\Filament\Resources\DistanceTariffs\DistanceTariffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDistanceTariffs extends ListRecords
{
    protected static string $resource = DistanceTariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
