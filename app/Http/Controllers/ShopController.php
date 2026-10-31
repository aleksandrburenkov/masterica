<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Technique;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ShopController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function index(Request $request): View
    {
        $query = Product::query()->published()->with(['variants', 'technique']);

        if ($techniqueSlug = $request->query('technique')) {
            $query->whereHas('technique', fn ($q) => $q->where('slug', $techniqueSlug));
        }

        $products = $query->paginate(9)->withQueryString();
        $techniques = Technique::query()->active()->get();

        $this->attachAvailability($products->getCollection());

        return view('pages.shop-index', compact('products', 'techniques'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_published, 404);

        $product->load(['variants' => fn ($q) => $q->where('is_active', true), 'technique']);

        $related = Product::query()
            ->published()
            ->whereKeyNot($product->getKey())
            ->take(3)
            ->get()
            ->load('variants');

        $this->attachAvailability($related->concat([$product]));

        return view('pages.shop-detail', compact('product', 'related'));
    }

    /**
     * Attach the current availability (on-hand minus reservations from live
     * orders) to every product and variant so cards always show up-to-date
     * quantities after any order is placed, cancelled, refunded or deleted.
     *
     * @param  Collection<int, Product>  $products
     */
    private function attachAvailability($products): void
    {
        $variantIds = $products
            ->flatMap(fn (Product $product) => $product->variants->pluck('id'))
            ->all();

        $available = $this->stock->availableForVariants($variantIds);

        foreach ($products as $product) {
            $total = 0;
            foreach ($product->variants as $variant) {
                $variantAvailable = max(0, $available[$variant->id] ?? 0);
                $variant->setAttribute('available_stock', $variantAvailable);
                $total += $variantAvailable;
            }
            $product->setAttribute('available_stock', $total);
        }
    }
}
