<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\Master;
use App\Models\Masterclass;
use App\Models\Review;
use App\Models\WorkshopSession;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $masterclasses = Masterclass::query()
            ->published()
            ->with('technique')
            ->orderBy('sort')
            ->take(3)
            ->get();

        $sessions = WorkshopSession::query()
            ->where('is_published', true)
            ->where('starts_at', '>', now())
            ->with(['masterclass', 'master'])
            ->orderBy('starts_at')
            ->take(4)
            ->get();

        $reviews = Review::query()
            ->published()
            ->with('user')
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        $masters = Master::query()->active()->take(1)->get();
        $guides = ContentPage::query()->published()->orderBy('sort')->take(2)->get();

        return view('home', compact('masterclasses', 'sessions', 'reviews', 'masters', 'guides'));
    }
}
