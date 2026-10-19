<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Models\Masterclass;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = Review::query()
            ->published()
            ->with(['user', 'masterclass'])
            ->orderByDesc('published_at')
            ->paginate(9);

        $masterclasses = Masterclass::query()->published()->orderBy('sort')->get();

        return view('pages.reviews-index', compact('reviews', 'masterclasses'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Review::class);

        $data = $request->validate([
            'masterclass_id' => ['nullable', 'integer', 'exists:masterclasses,id'],
            'type' => ['required', 'in:text,video'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'city' => ['nullable', 'string', 'max:120'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'consent_publish' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
        ]);

        Review::create([
            'user_id' => $request->user()->id,
            'masterclass_id' => $data['masterclass_id'] ?? null,
            'type' => $data['type'],
            'rating' => $data['rating'] ?? null,
            'body' => $data['body'],
            'city' => $data['city'] ?? null,
            'video_path' => $data['video_url'] ?? null,
            'is_demo' => false,
            'status' => ReviewStatus::Pending,
            'consent_publish' => true,
            'consent_marketing' => (bool) ($data['consent_marketing'] ?? false),
            'source' => 'Форма на сайте',
        ]);

        $message = 'Спасибо! Отзыв отправлен на модерацию и появится после проверки.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()
            ->route('reviews.index')
            ->with('success', $message);
    }
}
