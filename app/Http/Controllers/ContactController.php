<?php

namespace App\Http\Controllers;

use App\Services\ContactRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Форма «Напишите нам» на странице контактов. Хранится отдельно от заявок
 * на мастер-классы.
 */
class ContactController extends Controller
{
    public function __construct(
        private readonly ContactRequestService $service,
    ) {}

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['required', 'email', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'consent_data' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $contactRequest = $this->service->create($request->user(), $data);

        $message = 'Обращение '.$contactRequest->number.' принято. Студия ответит вам удобным способом.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'number' => $contactRequest->number,
            ], 201);
        }

        return redirect()->to($this->redirectTarget($request))->with('success', $message);
    }

    private function redirectTarget(Request $request): string
    {
        $source = trim((string) $request->input('source'));

        if ($source !== '' && ! str_contains($source, '://') && ! str_starts_with($source, '//')) {
            return url($source);
        }

        return route('contacts');
    }
}
