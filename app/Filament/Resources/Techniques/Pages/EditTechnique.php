<?php

namespace App\Filament\Resources\Techniques\Pages;

use App\Filament\Resources\Techniques\TechniqueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTechnique extends EditRecord
{
    protected static string $resource = TechniqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
