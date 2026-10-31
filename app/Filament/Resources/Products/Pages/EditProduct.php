<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductProvisioningService;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /**
     * После сохранения держим складскую запись товара в согласии с его ценой,
     * акцией, артикулом и размерами.
     */
    protected function afterSave(): void
    {
        app(ProductProvisioningService::class)->provision($this->record);
    }
}
