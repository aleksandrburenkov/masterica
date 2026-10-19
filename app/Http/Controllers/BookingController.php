<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Masterclass;
use App\Models\WorkshopSession;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function store(Request $request, Masterclass $masterclass): RedirectResponse
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
        ]);

        $session = WorkshopSession::query()
            ->whereKey($data['workshop_session_id'])
            ->where('masterclass_id', $masterclass->id)
            ->firstOrFail();

        $booking = $this->bookings->create($request->user(), $session, $data);

        $message = 'Запись '.$booking->number.' подтверждена. Оплата — в студии.';

        // Авторизованные клиенты видят запись в личном кабинете, гости — на
        // странице мастер-класса, где была заполнена форма.
        return ($request->user()
            ? redirect()->route('account.bookings')
            : redirect()->to(route('masterclasses.show', $masterclass->slug).'#booking'))
            ->with('success', $message);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $reason = $request->validate(['cancel_reason' => ['nullable', 'string', 'max:255']])['cancel_reason'] ?? null;

        $this->bookings->cancel($booking, $reason);

        return back()->with('success', 'Запись отменена.');
    }
}
