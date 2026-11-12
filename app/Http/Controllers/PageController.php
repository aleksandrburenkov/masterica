<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\LegalDocument;
use App\Models\Master;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $masters = Master::query()->active()->with('techniques')->get();

        return view('pages.about', compact('masters'));
    }

    public function howClassesWork(): View
    {
        return view('pages.how-classes-work');
    }

    public function contacts(): View
    {
        return view('pages.contacts');
    }

    public function guides(): View
    {
        $guides = ContentPage::query()->published()->active()->orderBy('sort')->get();

        return view('pages.guides-index', compact('guides'));
    }

    public function guide(ContentPage $page): View
    {
        abort_unless($page->is_published, 404);

        $others = ContentPage::query()
            ->published()
            ->active()
            ->whereKeyNot($page->getKey())
            ->take(2)
            ->get();

        return view('pages.guide', ['page' => $page, 'others' => $others]);
    }

    /** Показ документа по публичному слагу: /dokumenty/{slug}. */
    public function legal(LegalDocument $document): View
    {
        return view('pages.legal', ['document' => $document]);
    }
}
