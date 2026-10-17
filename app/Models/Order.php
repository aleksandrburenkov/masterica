<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number', 'user_id', 'status', 'payment_status', 'delivery_method',
    'subtotal_cents', 'delivery_cents', 'total_cents', 'currency',
    'contact_name', 'contact_phone', 'contact_email', 'address', 'comment',
    'placed_at', 'paid_at', 'completed_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'delivery_method' => DeliveryMethod::class,
            'subtotal_cents' => 'integer',
            'delivery_cents' => 'integer',
            'total_cents' => 'integer',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
