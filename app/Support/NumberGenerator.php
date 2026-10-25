<?php

namespace App\Support;

class NumberGenerator
{
    public static function booking(): string
    {
        return 'BK-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    public static function order(): string
    {
        return 'OR-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    public static function outreach(): string
    {
        return 'OUT-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    public static function contactRequest(): string
    {
        return 'CT-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}
