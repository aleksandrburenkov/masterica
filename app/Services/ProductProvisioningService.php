<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Товар в магазине не имеет вариативности, поэтому отдельная номенклатура
 * вручную не заводится. Однако остатки и склад (а также корзина и заказы)
 * работают со складской записью-вариантом: этот сервис автоматически
 * провизионит ровно одну такую запись для товара и синхронизирует её цену,
 * артикул и название с данными товара.
 */
class ProductProvisioningService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    /**
     * Создаёт или обновляет единственную складскую запись товара и, при
     * необходимости, оформляет начальный приход.
     */
    public function provision(Product $product, int $initialStock = 0, ?User $actor = null): ProductVariant
    {
        return DB::transaction(function () use ($product, $initialStock, $actor): ProductVariant {
            $variant = $product->defaultVariant();
            $sizes = trim((string) $product->sizes);

            $attributes = [
                'sku' => $product->sku,
                'price_cents' => $product->effectivePriceCents(),
                'is_active' => true,
            ];

            if ($sizes !== '') {
                $attributes['name'] = $sizes;
            }

            if ($variant) {
                $variant->fill($attributes)->save();
            } else {
                $variant = $product->variants()->create($attributes + [
                    'name' => $sizes !== '' ? $sizes : 'Стандарт',
                    'stock' => 0,
                ]);
            }

            if ($initialStock > 0) {
                $this->stock->record(
                    $variant,
                    StockMovementType::Receipt,
                    $initialStock,
                    'Начальный приход при создании товара',
                    $actor,
                );
            }

            return $variant->refresh();
        });
    }
}
