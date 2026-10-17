<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'masterclass_id', 'master_id', 'starts_at', 'timezone', 'capacity',
    'price_cents', 'location_name', 'address', 'status', 'is_published',
])]
class WorkshopSession extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'status' => SessionStatus::class,
            'is_published' => 'boolean',
            'capacity' => 'integer',
            'price_cents' => 'integer',
        ];
    }

    public function masterclass(): BelongsTo
    {
        return $this->belongsTo(Masterclass::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function priceCents(): int
    {
        return $this->price_cents ?? $this->masterclass->price_cents;
    }

    /** Seats already taken by non-expired, non-cancelled bookings. */
    public function bookedSeats(): int
    {
        return (int) $this->bookings()
            ->whereIn('status', $this->occupyingStatuses())
            ->sum('participants_count');
    }

    /** Occupied seats including unexpired holds. */
    public function reservedSeats(): int
    {
        return (int) $this->bookings()
            ->whereIn('status', $this->occupyingStatuses())
            ->where(function ($q) {
                $q->where('status', '!=', BookingStatus::Held->value)
                    ->orWhere('hold_expires_at', '>', now());
            })
            ->sum('participants_count');
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->reservedSeats());
    }

    public function isBookable(): bool
    {
        return $this->is_published
            && $this->status === SessionStatus::Scheduled
            && $this->starts_at->isFuture()
            && $this->seatsLeft() > 0;
    }

    private function occupyingStatuses(): array
    {
        return [
            BookingStatus::Held->value,
            BookingStatus::PendingPayment->value,
            BookingStatus::Confirmed->value,
            BookingStatus::Attended->value,
        ];
    }
}
