<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Technique;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->published()->with(['variants', 'technique']);

        if ($techniqueSlug = $request->query('technique')) {
            $query->whereHas('technique', fn ($q) => $q->where('slug', $techniqueSlug));
        }

        $products = $query->paginate(9)->withQueryString();
        $techniques = Technique::query()->active()->get();

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
            ->get();

        return view('pages.shop-detail', compact('product', 'related'));
    }
}
