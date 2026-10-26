<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\WorkshopSession;
use App\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Atomically reserve a seat and register a new local booking.
     *
     * The request arrives with the «Новая» status: the administrator reviews
     * and confirms it later in the panel. A null user means a guest booking
     * made without an account: contact details are stored on the booking
     * itself. The session row is locked for the duration of the transaction so
     * two concurrent requests for the last seat cannot both succeed.
     * Server-side data (price, capacity, publication) is always re-read and
     * re-checked.
     */
    public function create(?User $user, WorkshopSession $session, array $data): Booking
    {
        return DB::transaction(function () use ($user, $session, $data) {
            $this->expireStaleHolds();

            /** @var WorkshopSession $locked */
            $locked = WorkshopSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertBookable($locked);

            $participants = max(1, (int) $data['participants_count']);

            if ($this->hasActiveBooking($user, $locked, $data['contact_email'])) {
                throw ValidationException::withMessages([
                    'participants_count' => 'На этот сеанс у вас уже есть активная запись.',
                ]);
            }

            $reserved = $this->reservedSeats($locked);
            if ($reserved + $participants > $locked->capacity) {
                throw ValidationException::withMessages([
                    'participants_count' => 'На этот сеанс недостаточно свободных мест.',
                ]);
            }

            $unit = $locked->priceCents();

            $attributes = [
                'number' => NumberGenerator::booking(),
                'status' => BookingStatus::New,
                'payment_status' => PaymentStatus::Unpaid,
                'participants_count' => $participants,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'],
                'child_age' => $data['child_age'] ?? null,
                'unit_price_cents' => $unit,
                'total_cents' => $unit * $participants,
                'currency' => $locked->masterclass->currency ?? 'RUB',
                'hold_expires_at' => null,
                'confirmed_at' => null,
                'attended_at' => null,
                'canceled_at' => null,
                'cancel_reason' => null,
                'notes' => $data['notes'] ?? null,
                'consent_data' => (bool) ($data['consent_data'] ?? false),
                'consent_marketing' => (bool) ($data['consent_marketing'] ?? false),
            ];

            if ($user !== null) {
                // A cancelled booking may be reused so the same user can
                // re-book the same session without violating the unique
                // (user, session) index.
                $booking = Booking::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'workshop_session_id' => $locked->id,
                    ],
                    $attributes,
                );
            } else {
                // Guests have no account, so every accepted request creates a
                // new record. Duplicate active bookings are already blocked
                // above by contact e-mail.
                $booking = Booking::query()->create($attributes + [
                    'user_id' => null,
                    'workshop_session_id' => $locked->id,
                ]);
            }

            // Заявка поступает в админку со статусом «Новая». Подтверждение и
            // приём оплаты выполняет сотрудник студии; пользователь получает
            // уведомление о принятой записи (текст уведомления не меняется).
            $this->notifySubmission($booking);

            return $booking->refresh();
        });
    }

    public function confirm(Booking $booking, ?string $reason = null): Booking
    {
        $booking->forceFill([
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => $booking->confirmed_at ?? now(),
            'hold_expires_at' => null,
        ])->save();

        $this->audit->log($booking->user, 'booking.confirmed', $booking, $reason, [
            'status' => BookingStatus::Confirmed->value,
        ]);

        return $booking;
    }

    /**
     * Registration side effects for a freshly submitted request: an audit
     * entry and the user notification. The notification text is intentionally
     * unchanged from the previous auto-confirmation flow.
     */
    private function notifySubmission(Booking $booking): void
    {
        $this->audit->log($booking->user, 'booking.created', $booking, 'Заявка на мастер-класс', [
            'status' => BookingStatus::New->value,
        ]);

        $this->notifications->notify(
            $booking->user,
            'Запись подтверждена',
            'Запись '.$booking->number.' подтверждена. Оплата — в студии.',
        );
    }

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        if (! $booking->status->isActive()) {
            return $booking;
        }

        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'canceled_at' => now(),
            'cancel_reason' => $reason,
            'hold_expires_at' => null,
        ])->save();

        $this->audit->log($booking->user, 'booking.cancelled', $booking, $reason);

        return $booking;
    }

    /**
     * Idempotently expire holds whose timeout has passed. Never touches the
     * payment status, so a browser refresh cannot alter finances.
     */
    public function expireStaleHolds(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Held->value)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->update(['status' => BookingStatus::Expired->value, 'updated_at' => now()]);
    }

    private function assertBookable(WorkshopSession $session): void
    {
        if (! $session->is_published || $session->status !== SessionStatus::Scheduled) {
            throw ValidationException::withMessages([
                'session' => 'Этот сеанс больше не доступен для записи.',
            ]);
        }

        if ($session->starts_at->isPast()) {
            throw ValidationException::withMessages([
                'session' => 'Этот сеанс уже прошёл.',
            ]);
        }
    }

    /**
     * An authenticated user is matched by account; a guest is matched by the
     * contact e-mail so the same person cannot double-book a session.
     */
    private function hasActiveBooking(?User $user, WorkshopSession $session, ?string $email): bool
    {
        return Booking::query()
            ->where('workshop_session_id', $session->id)
            ->whereIn('status', [
                BookingStatus::New->value,
                BookingStatus::Held->value,
                BookingStatus::PendingPayment->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Attended->value,
            ])
            ->when(
                $user !== null,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->whereNull('user_id')->where('contact_email', $email),
            )
            ->exists();
    }

    private function reservedSeats(WorkshopSession $session): int
    {
        return (int) Booking::query()
            ->where('workshop_session_id', $session->id)
            ->where(function ($q) {
                $q->whereIn('status', [
                    BookingStatus::New->value,
                    BookingStatus::PendingPayment->value,
                    BookingStatus::Confirmed->value,
                    BookingStatus::Attended->value,
                ])->orWhere(function ($q2) {
                    $q2->where('status', BookingStatus::Held->value)
                        ->where('hold_expires_at', '>', now());
                });
            })
            ->sum('participants_count');
    }
}
