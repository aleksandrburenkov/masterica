<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'number', 'user_id', 'workshop_session_id', 'status', 'payment_status',
    'participants_count', 'contact_name', 'contact_phone', 'contact_email',
    'child_age', 'unit_price_cents', 'total_cents', 'currency',
    'hold_expires_at', 'confirmed_at', 'attended_at', 'canceled_at',
    'cancel_reason', 'notes',
])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'hold_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'attended_at' => 'datetime',
            'canceled_at' => 'datetime',
            'participants_count' => 'integer',
            'child_age' => 'integer',
            'unit_price_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkshopSession::class, 'workshop_session_id');
    }

    public function isHoldExpired(): bool
    {
        return $this->status === BookingStatus::Held
            && $this->hold_expires_at !== null
            && $this->hold_expires_at->isPast();
    }
}
