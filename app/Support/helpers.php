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

if (! function_exists('media_url')) {
    /**
     * Build a public URL for an image path.
     *
     * Legacy images live directly in the public directory (images/...), while
     * images uploaded through the admin panel are stored on the "public"
     * filesystem disk and served through the /storage symlink.
     */
    function media_url(?string $path, string $fallback = ''): string
    {
        $path = $path ?: $fallback;

        if ($path === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $path) === 1) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'images/') || str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
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
