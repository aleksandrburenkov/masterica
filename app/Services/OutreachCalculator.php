<?php

namespace App\Services;

use App\Support\Settings;

/**
 * Calculates the outreach (off-site) master class quote from local settings.
 * This is a request quote, not a payment checkout.
 */
class OutreachCalculator
{
    /**
     * @return array{base_price_cents:int,included_participants:int,extra_count:int,extra_price_cents:int,total_cents:int,currency:string}
     */
    public function calculate(int $participants): array
    {
        $participants = max(1, $participants);

        $base = Settings::int('pricing.outreach_base_cents', 800000);
        $included = max(1, Settings::int('pricing.outreach_included', 8));
        $extraPrice = Settings::int('pricing.outreach_extra_cents', 70000);
        $extraCount = max(0, $participants - $included);

        return [
            'base_price_cents' => $base,
            'included_participants' => $included,
            'extra_count' => $extraCount,
            'extra_price_cents' => $extraPrice,
            'total_cents' => $base + ($extraCount * $extraPrice),
            'currency' => setting('rules.currency', 'RUB'),
        ];
    }
}
