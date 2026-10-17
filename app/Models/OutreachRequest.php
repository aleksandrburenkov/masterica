<?php

namespace App\Models;

use App\Enums\OutreachStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'number', 'user_id', 'direction', 'requested_date', 'location',
    'participants_count', 'age_group', 'contact_name', 'contact_phone',
    'contact_email', 'base_price_cents', 'included_participants', 'extra_count',
    'extra_price_cents', 'calc_total_cents', 'currency', 'status', 'comment',
])]
class OutreachRequest extends Model
{
    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'status' => OutreachStatus::class,
            'participants_count' => 'integer',
            'base_price_cents' => 'integer',
            'included_participants' => 'integer',
            'extra_count' => 'integer',
            'extra_price_cents' => 'integer',
            'calc_total_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
