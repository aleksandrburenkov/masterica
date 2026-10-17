<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\Course;
use App\Models\Masterclass;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $masterclasses = collect();
        $products = collect();
        $courses = collect();
        $guides = collect();

        if (mb_strlen($q) >= 2) {
            $like = '%'.$q.'%';

            $masterclasses = Masterclass::query()->published()
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('short_description', 'like', $like))
                ->take(6)->get();

            $products = Product::query()->published()
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('short_description', 'like', $like))
                ->take(6)->get();

            $courses = Course::query()->published()
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('short_description', 'like', $like))
                ->take(6)->get();

            $guides = ContentPage::query()->published()
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
                ->take(6)->get();
        }

        return view('pages.search', compact('q', 'masterclasses', 'products', 'courses', 'guides'));
    }
}
