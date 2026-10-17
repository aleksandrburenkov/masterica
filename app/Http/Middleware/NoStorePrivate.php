<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents private pages (account, checkout, paid lessons) from being cached
 * by shared/public caches.
 */
class NoStorePrivate
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
