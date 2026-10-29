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

        $masterclasses = Masterclass::query()
            ->published()
            ->with('technique')
            ->orderBy('sort')
            ->get();

        return view('pages.masterclasses-index', [
            'masterclasses' => $masterclasses,
            'techniques' => $techniques,
            'requestMasterclass' => $this->resolveRequestMasterclass($request),
        ]);
    }

    /**
     * Мастер-класс для режима запроса в форме «Оставить заявку». Клиент может
     * передать только слаг: название и признак отсутствия сеансов проверяются
     * на сервере по базе, поэтому подменить данные формы нельзя.
     */
    private function resolveRequestMasterclass(Request $request): ?Masterclass
    {
        $slug = $request->query('request');

        if (! is_string($slug)) {
            return null;
        }

        $slug = trim($slug);

        if ($slug === '' || mb_strlen($slug) > 255) {
            return null;
        }

        $masterclass = Masterclass::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($masterclass === null || $masterclass->hasActiveSessions()) {
            return null;
        }

        return $masterclass;
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

        $reviews = $masterclass->reviews()->published()->with(['user', 'video'])->orderByDesc('published_at')->take(3)->get();

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
