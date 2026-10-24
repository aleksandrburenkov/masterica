<?php

namespace App\Filament\Resources\DistanceTariffs\Pages;

use App\Filament\Resources\DistanceTariffs\DistanceTariffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDistanceTariff extends EditRecord
{
    protected static string $resource = DistanceTariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
