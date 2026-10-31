<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductProvisioningService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** Начальный приход вводится только при создании и не является колонкой товара. */
    protected int $initialStock = 0;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialStock = max(0, (int) ($data['initial_stock'] ?? 0));
        unset($data['initial_stock']);

        return $data;
    }

    /**
     * Товар без вариативности: при создании сразу заводим его складскую
     * запись с ценой и артикулом и оформляем начальный приход.
     */
    protected function afterCreate(): void
    {
        app(ProductProvisioningService::class)->provision(
            $this->record,
            $this->initialStock,
            auth()->user(),
        );
    }
}
