<?php

namespace App\Filament\Resources\Techniques\Pages;

use App\Filament\Resources\Techniques\TechniqueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTechniques extends ListRecords
{
    protected static string $resource = TechniqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
