<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves the page a guest wanted to reach before being asked to log in.
 *
 * When the auth middleware sends a guest to the login form, the framework
 * remembers the intended URL in the session. The same intention may also travel
 * through the login / registration forms as an explicit, validated local path.
 */
class RedirectIntent
{
    /** Explicit local path carried through the auth forms, if any. */
    public static function explicit(Request $request): ?string
    {
        return self::sanitize($request->input('redirect'));
    }

    /**
     * Destination for a successful login or registration: the explicit form
     * value first, then the framework's remembered intended URL, then the
     * provided default.
     */
    public static function resolve(Request $request, string $default): string
    {
        return self::explicit($request) ?? redirect()->intended($default)->getTargetUrl();
    }

    /**
     * Only safe local paths are accepted. Protocol-relative (`//host`),
     * absolute URLs and other escapes are rejected to prevent open redirects.
     */
    public static function sanitize(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return preg_match('#^/(?!/)#', $value) === 1 ? $value : null;
    }
}
