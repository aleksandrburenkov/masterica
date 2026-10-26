<?php

namespace App\Models;

use App\Enums\ContactRequestForm;
use App\Enums\OutreachStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    /**
     * Запрос мастер-класса без активных сеансов формирует текст обращения
     * на сервере через Masterclass::requestLabel() — он всегда начинается
     * с этого префикса, что отличает такие обращения от свободного текста
     * формы на странице «Контакты».
     */
    public const MASTERCLASS_REQUEST_PREFIX = 'Запрос';

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

    public function isMasterclassRequest(): bool
    {
        return str_starts_with(trim((string) $this->message), self::MASTERCLASS_REQUEST_PREFIX);
    }

    public function formSource(): ContactRequestForm
    {
        return $this->isMasterclassRequest()
            ? ContactRequestForm::MasterclassRequest
            : ContactRequestForm::Contacts;
    }

    public function scopeForForm(Builder $query, ContactRequestForm $form): Builder
    {
        return match ($form) {
            ContactRequestForm::MasterclassRequest => $query
                ->where('message', 'like', self::MASTERCLASS_REQUEST_PREFIX.'%'),
            ContactRequestForm::Contacts => $query
                ->where(fn (Builder $query) => $query
                    ->where('message', 'not like', self::MASTERCLASS_REQUEST_PREFIX.'%')
                    ->orWhereNull('message')),
        };
    }
}
