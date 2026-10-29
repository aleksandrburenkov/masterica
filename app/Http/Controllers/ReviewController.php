<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Models\Masterclass;
use App\Models\Review;
use App\Services\ReviewVideoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewVideoService $videos,
    ) {}

    public function index(): View
    {
        $reviews = Review::query()
            ->published()
            ->with(['user', 'masterclass', 'video'])
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
            'video_url' => ['nullable', 'url:http,https', 'max:255'],
            'video_file' => [
                'nullable',
                'file',
                'extensions:'.implode(',', (array) config('video.allowed_extensions', [])),
                'max:'.(int) config('video.max_size_kb', 204800),
            ],
            'consent_data' => ['accepted'],
            'consent_publish' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
        ]);

        $hasFile = $request->hasFile('video_file');

        if ($data['type'] === 'video' && ! $hasFile && empty($data['video_url'])) {
            throw ValidationException::withMessages([
                'video_file' => 'Прикрепите видеофайл или укажите ссылку на видео.',
            ]);
        }

        $review = DB::transaction(function () use ($request, $data, $hasFile): Review {
            $review = Review::create([
                'user_id' => $request->user()->id,
                'masterclass_id' => $data['masterclass_id'] ?? null,
                'type' => $data['type'],
                'rating' => $data['rating'] ?? null,
                'body' => $data['body'],
                'city' => $data['city'] ?? null,
                'video_path' => $data['video_url'] ?? null,
                'is_demo' => false,
                'status' => ReviewStatus::Pending,
                'consent_data' => true,
                'consent_publish' => true,
                'consent_marketing' => (bool) ($data['consent_marketing'] ?? false),
                'source' => 'Форма на сайте',
            ]);

            if ($hasFile) {
                $this->videos->store($review, $request->file('video_file'), $request->user());
                $review->load('video');
            }

            return $review;
        });

        $message = 'Спасибо! Отзыв отправлен на модерацию и появится после проверки.';

        if ($hasFile && $review->video?->scan_status?->isThreat()) {
            $message = 'Отзыв отправлен на модерацию. Видео не прошло проверку безопасности и не будет опубликовано.';
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()
            ->route('reviews.index')
            ->with('success', $message);
    }

    /**
     * Отдаёт видеофайл отзыва с поддержкой частичных запросов (HTTP Range).
     * Файл лежит на приватном диске и доступен только после проверки прав.
     */
    public function video(Review $review): BinaryFileResponse
    {
        $this->authorize('viewVideo', $review);

        $video = $review->video()->first();
        abort_unless($video && $video->isPlayable(), 404);

        $disk = Storage::disk($video->disk ?: (string) config('video.disk', 'local'));
        abort_unless($disk->exists($video->path), 404);

        $extension = preg_replace('/[^a-z0-9]/', '', (string) $video->extension) ?: 'mp4';

        return response()->file($disk->path($video->path), [
            'Content-Type' => $video->mime_type ?: 'video/mp4',
            'Content-Disposition' => 'inline; filename="review-'.$review->id.'.'.$extension.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => $this->cacheControl($review),
        ]);
    }

    /**
     * Отдаёт кадр-обложку видеоотзыва.
     */
    public function poster(Review $review): BinaryFileResponse
    {
        $this->authorize('viewVideo', $review);

        $video = $review->video()->first();
        abort_unless($video && is_string($video->poster_path) && $video->poster_path !== '', 404);

        $disk = Storage::disk($video->disk ?: (string) config('video.disk', 'local'));
        abort_unless($disk->exists($video->poster_path), 404);

        return response()->file($disk->path($video->poster_path), [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $this->cacheControl($review),
        ]);
    }

    private function cacheControl(Review $review): string
    {
        return $review->status === ReviewStatus::Approved
            ? 'public, max-age=604800'
            : 'private, no-store';
    }
}
