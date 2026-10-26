<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Masterclass;
use App\Models\WorkshopSession;
use App\Services\BookingService;
use App\Services\ContactRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly ContactRequestService $contactRequests,
    ) {}

    public function store(Request $request, Masterclass $masterclass): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'workshop_session_id' => ['required', 'integer'],
            'participants_count' => ['required', 'integer', 'min:1', 'max:8'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['required', 'email', 'max:160'],
            'child_age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent_data' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
        ]);

        $session = WorkshopSession::query()
            ->whereKey($data['workshop_session_id'])
            ->where('masterclass_id', $masterclass->id)
            ->firstOrFail();

        $booking = $this->bookings->create($request->user(), $session, $data);

        $message = 'Запись '.$booking->number.' подтверждена. Оплата — в студии.';

        // AJAX-ответ позволяет показать подтверждение без перезагрузки и
        // прокрутки страницы. Обычная отправка формы остаётся запасным путём.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'number' => $booking->number,
            ], 201);
        }

        return redirect()
            ->route('masterclasses.show', $masterclass->slug)
            ->with('success', $message);
    }

    /**
     * Запись из нижней формы «Оставить заявку»: конкретный сеанс выбирается
     * в форме, мастер-класс определяется по сеансу.
     */
    public function storeQuick(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'workshop_session_id' => ['required', 'integer'],
            'participants_count' => ['required', 'integer', 'min:1', 'max:8'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['required', 'email', 'max:160'],
            'child_age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent_data' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $session = WorkshopSession::query()->whereKey($data['workshop_session_id'])->firstOrFail();

        if (! $session->isBookable()) {
            throw ValidationException::withMessages([
                'workshop_session_id' => 'На выбранную дату нет свободных мест. Выберите другой сеанс.',
            ]);
        }

        $booking = $this->bookings->create($request->user(), $session, $data);

        $message = 'Запись '.$booking->number.' подтверждена. Оплата — в студии.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'number' => $booking->number,
            ], 201);
        }

        return redirect()->to($this->redirectTarget($request))->with('success', $message);
    }

    /**
     * Запрос на мастер-класс, у которого нет активных сеансов. Клиент присылает
     * только слаг мастер-класса; название и текст запроса формируются на сервере
     * по базе, поэтому подмена данных со стороны клиента не влияет на результат.
     */
    public function storeRequest(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'request_masterclass' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['required', 'email', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent_data' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $masterclass = Masterclass::query()
            ->published()
            ->where('slug', $data['request_masterclass'])
            ->first();

        if ($masterclass === null || $masterclass->hasActiveSessions()) {
            throw ValidationException::withMessages([
                'request_masterclass' => 'Для этого мастер-класса доступны сеансы. Выберите дату в расписании.',
            ]);
        }

        $message = $masterclass->requestLabel();
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($notes !== '') {
            $message .= "\n\nКомментарий: ".$notes;
        }

        $this->contactRequests->create($request->user(), [
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'contact_email' => $data['contact_email'],
            'message' => $message,
            'consent_data' => $data['consent_data'],
            'consent_marketing' => $data['consent_marketing'] ?? false,
            'source' => $data['source'] ?? null,
        ]);

        $responseMessage = 'Ваш запрос принят и обрабатывается, ответ будет отправлен по Email';

        if ($request->expectsJson()) {
            return response()->json(['message' => $responseMessage], 201);
        }

        return redirect()->to($this->redirectTarget($request))->with('success', $responseMessage);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $reason = $request->validate(['cancel_reason' => ['nullable', 'string', 'max:255']])['cancel_reason'] ?? null;

        $this->bookings->cancel($booking, $reason);

        return back()->with('success', 'Запись отменена.');
    }

    private function redirectTarget(Request $request): string
    {
        $source = trim((string) $request->input('source'));

        if ($source !== '' && ! str_contains($source, '://') && ! str_starts_with($source, '//')) {
            return url($source);
        }

        return route('home');
    }
}
