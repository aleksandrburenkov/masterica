<?php

namespace App\Services;

use App\Models\DistanceTariff;
use App\Models\Masterclass;

/**
 * Calculates the outreach (off-site) master class quote from a masterclass
 * card. This is a request quote, not a payment checkout.
 */
class OutreachCalculator
{
    /**
     * @return array{base_price_cents:int,included_participants:int,extra_count:int,percent:float,surcharge_cents:int,distance_km:float|null,distance_surcharge_per_participant_cents:int,distance_surcharge_cents:int,total_cents:int,duration_minutes:int,currency:string}
     */
    public function calculate(int $participants, ?Masterclass $masterclass = null, ?float $distanceKm = null): array
    {
        $participants = max(0, $participants);
        $included = $masterclass ? max(0, (int) $masterclass->included_participants) : 0;
        $base = $masterclass ? $masterclass->outreachPriceCents() * $participants : 0;
        $extraCount = $masterclass ? max(0, $participants - $included) : 0;
        $percent = $extraCount > 0 ? $this->surchargePercent($masterclass, $extraCount) : 0.0;
        $surcharge = (int) round($base * $percent / 100);

        $tariff = DistanceTariff::forDistance($distanceKm);
        $distancePerParticipant = $tariff?->surchargeCents() ?? 0;
        $distanceSurcharge = $distancePerParticipant * $participants;

        return [
            'base_price_cents' => $base,
            'included_participants' => $included,
            'extra_count' => $extraCount,
            'percent' => $percent,
            'surcharge_cents' => $surcharge,
            'distance_km' => $distanceKm,
            'distance_surcharge_per_participant_cents' => $distancePerParticipant,
            'distance_surcharge_cents' => $distanceSurcharge,
            'total_cents' => $base + $surcharge + $distanceSurcharge,
            'duration_minutes' => (int) ($masterclass?->duration_minutes ?? 0),
            'currency' => setting('rules.currency', 'RUB'),
        ];
    }

    /**
     * Процент за дополнительных участников: берём первую ступень,
     * которая покрывает их количество, иначе — максимальную ступень.
     */
    private function surchargePercent(Masterclass $masterclass, int $extraCount): float
    {
        $tiers = collect($masterclass->participant_surcharges ?? [])
            ->mapWithKeys(function ($percent, $count) {
                $count = (int) $count;
                $percent = (float) str_replace(',', '.', (string) $percent);

                return $count > 0 ? [$count => $percent] : [];
            })
            ->filter(fn (float $percent) => $percent > 0)
            ->sortKeys();

        if ($tiers->isEmpty()) {
            return 0.0;
        }

        $matched = $tiers->first(fn (float $percent, int $count) => $extraCount <= $count);

        return (float) ($matched ?? $tiers->last());
    }
}
