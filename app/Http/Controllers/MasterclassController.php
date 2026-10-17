<?php

namespace App\Http\Controllers;

use App\Models\Masterclass;
use App\Models\Technique;
use App\Models\WorkshopSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MasterclassController extends Controller
{
    public function index(Request $request): View
    {
        $techniques = Technique::query()->active()->get();

        $query = Masterclass::query()->published()->with('technique');

        if ($techniqueSlug = $request->query('technique')) {
            $query->whereHas('technique', fn ($q) => $q->where('slug', $techniqueSlug));
        }

        if ($age = $request->query('age')) {
            $query->where('age_min', '<=', (int) $age)->where(fn ($q) => $q->whereNull('age_max')->orWhere('age_max', '>=', (int) $age));
        }

        if ($level = $request->query('level')) {
            $query->where('level', $level);
        }

        if ($format = $request->query('format')) {
            $query->where('format', $format);
        }

        $hasFilters = (bool) array_filter($request->only(['technique', 'age', 'level', 'format']), fn ($v) => $v !== null && $v !== '');

        $masterclasses = $query->orderBy('sort')->paginate(9)->withQueryString();

        return view('pages.masterclasses-index', [
            'masterclasses' => $masterclasses,
            'techniques' => $techniques,
            'hasFilters' => $hasFilters,
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
