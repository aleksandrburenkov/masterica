<?php

namespace App\Services;

use App\Enums\OutreachStatus;
use App\Models\Masterclass;
use App\Models\OutreachRequest;
use App\Models\User;
use App\Support\NumberGenerator;

class OutreachService
{
    public function __construct(
        private readonly OutreachCalculator $calculator,
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Create an outreach workshop request. The price is recalculated on the
     * server and stored with the request; this is a quote, not a checkout.
     */
    public function create(?User $user, array $data): OutreachRequest
    {
        $participants = (int) $data['participants_count'];
        $masterclass = $this->resolveMasterclass($data['direction']);
        $distanceKm = isset($data['distance_km']) && $data['distance_km'] !== ''
            ? (float) $data['distance_km']
            : null;
        $calc = $this->calculator->calculate($participants, $masterclass, $distanceKm);

        $request = OutreachRequest::create([
            'number' => NumberGenerator::outreach(),
            'user_id' => $user?->id,
            'direction' => $masterclass?->title ?? $data['direction'],
            'requested_date' => $data['requested_date'],
            'location' => $data['location'],
            'distance_km' => $calc['distance_km'],
            'participants_count' => $participants,
            'age_group' => $data['age_group'] ?? null,
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'contact_email' => $data['contact_email'],
            'base_price_cents' => $calc['base_price_cents'],
            'included_participants' => $calc['included_participants'],
            'extra_count' => $calc['extra_count'],
            'extra_price_cents' => $calc['surcharge_cents'],
            'distance_surcharge_cents' => $calc['distance_surcharge_cents'],
            'calc_total_cents' => $calc['total_cents'],
            'currency' => $calc['currency'],
            'status' => OutreachStatus::New,
            'comment' => $data['comment'] ?? null,
        ]);

        $this->audit->log($user, 'outreach.requested', $request, null, [
            'total_cents' => $request->calc_total_cents,
            'percent' => $calc['percent'],
            'distance_km' => $calc['distance_km'],
            'distance_surcharge_cents' => $calc['distance_surcharge_cents'],
        ]);
        $this->notifications->notify(
            $user,
            'Заявка на выездной мастер-класс принята',
            'Заявка '.$request->number.'. Мы свяжемся для подтверждения.',
        );

        return $request;
    }

    /**
     * Направление приходит слагом (калькулятор) или названием (общая форма).
     */
    private function resolveMasterclass(string $direction): ?Masterclass
    {
        return Masterclass::query()
            ->published()
            ->where(fn ($query) => $query->where('slug', $direction)->orWhere('title', $direction))
            ->first();
    }
}
