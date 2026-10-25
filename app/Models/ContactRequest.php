<?php

namespace App\Models;

use App\Enums\OutreachStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Обращение из формы на странице контактов.
 */
#[Fillable([
    'number', 'user_id', 'contact_name', 'contact_phone', 'contact_email',
    'message', 'status', 'consent_data', 'consent_marketing', 'source',
])]
class ContactRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OutreachStatus::class,
            'consent_data' => 'boolean',
            'consent_marketing' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
