<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\LegalDocument;
use App\Models\Master;
use App\Support\LegalDocuments;
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
        $guides = ContentPage::query()->published()->orderBy('sort')->get();

        return view('pages.guides-index', compact('guides'));
    }

    public function guide(ContentPage $page): View
    {
        abort_unless($page->is_published, 404);

        $others = ContentPage::query()
            ->published()
            ->whereKeyNot($page->getKey())
            ->take(2)
            ->get();

        return view('pages.guide', ['page' => $page, 'others' => $others]);
    }

    public function privacy(): View
    {
        return $this->legalPage('privacy_policy');
    }

    public function consent(): View
    {
        return $this->legalPage('consent_personal_data');
    }

    public function rules(): View
    {
        return $this->legalPage('booking_rules');
    }

    /** Показ документа по публичному слагу: /dokumenty/{slug}. */
    public function legal(LegalDocument $document): View
    {
        return view('pages.legal', ['document' => $document]);
    }

    private function legalPage(string $key): View
    {
        $document = LegalDocuments::get($key);

        abort_if($document === null, 404);

        return view('pages.legal', ['document' => $document]);
    }
}
