<?php

use App\Support\LegalDocuments;
use App\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Read a value from the studio settings repository.
     */
    function setting(string $key, ?string $default = null): ?string
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('legal')) {
    /**
     * Read a legally-significant document field (privacy policy, consent, offer…).
     * Documents live in the `legal_documents` table and are editable in Filament.
     */
    function legal(string $key, string $field = 'lead', ?string $default = null): ?string
    {
        return LegalDocuments::field($key, $field, $default);
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(string $currency = 'RUB'): string
    {
        return match (strtoupper($currency)) {
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
            default => $currency,
        };
    }
}

if (! function_exists('money')) {
    /**
     * Format an integer amount in the smallest currency unit (kopecks)
     * for display, e.g. 150000 => "1 500 ₽".
     */
    function money(int $cents, string $currency = 'RUB'): string
    {
        $rubles = intdiv($cents, 100);
        $remainder = abs($cents % 100);
        $formatted = number_format($rubles, 0, ',', ' ');
        if ($remainder !== 0) {
            $formatted .= ','.str_pad((string) $remainder, 2, '0', STR_PAD_LEFT);
        }

        return $formatted.' '.currency_symbol($currency);
    }
}
