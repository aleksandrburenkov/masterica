<?php

namespace App\Services;

use App\Enums\OutreachStatus;
use App\Models\ContactRequest;
use App\Models\User;
use App\Support\NumberGenerator;

/**
 * Обращения из формы на странице контактов.
 */
class ContactRequestService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function create(?User $user, array $data): ContactRequest
    {
        $request = ContactRequest::create([
            'number' => NumberGenerator::contactRequest(),
            'user_id' => $user?->id,
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'contact_email' => $data['contact_email'],
            'message' => $data['message'],
            'status' => OutreachStatus::New,
            'consent_data' => (bool) ($data['consent_data'] ?? false),
            'consent_marketing' => (bool) ($data['consent_marketing'] ?? false),
            'source' => $data['source'] ?? null,
        ]);

        $this->audit->log($user, 'contact.requested', $request, null, [
            'source' => $request->source,
        ]);
        $this->notifications->notify(
            $user,
            'Обращение принято',
            'Обращение '.$request->number.'. Студия ответит вам удобным способом.',
        );

        return $request;
    }
}
