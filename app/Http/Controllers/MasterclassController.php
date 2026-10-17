<?php

namespace App\Http\Controllers;

use App\Models\Masterclass;
use App\Models\Technique;
use App\Models\WorkshopSession;
use Illuminate\Contracts\View\View;

class MasterclassController extends Controller
{
    public function index(): View
    {
        $techniques = Technique::query()->active()->get();

        $masterclasses = Masterclass::query()
            ->published()
            ->with('technique')
            ->orderBy('sort')
            ->get();

        return view('pages.masterclasses-index', [
            'masterclasses' => $masterclasses,
            'techniques' => $techniques,
        ]);
    }

    public function show(Masterclass $masterclass): View
    {
        abort_unless($masterclass->is_published, 404);

        $masterclass->load('technique');

        $sessions = WorkshopSession::query()
            ->where('masterclass_id', $masterclass->id)
            ->where('is_published', true)
            ->where('starts_at', '>', now())
            ->with('master')
            ->orderBy('starts_at')
            ->get();

        $related = Masterclass::query()
            ->published()
            ->whereKeyNot($masterclass->getKey())
            ->with('technique')
            ->orderBy('sort')
            ->take(3)
            ->get();

        $reviews = $masterclass->reviews()->published()->with('user')->orderByDesc('published_at')->take(3)->get();

        return view('pages.masterclass-detail', compact('masterclass', 'sessions', 'related', 'reviews'));
    }

    public function bryansk(): View
    {
        $masterclasses = Masterclass::query()->published()->with('technique')->orderBy('sort')->get();

        $sessions = WorkshopSession::query()
            ->where('is_published', true)
            ->where('starts_at', '>', now())
            ->with(['masterclass', 'master'])
            ->orderBy('starts_at')
            ->take(8)
            ->get();

        return view('pages.bryansk', compact('masterclasses', 'sessions'));
    }
}
