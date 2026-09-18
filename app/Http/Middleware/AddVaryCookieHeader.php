<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents CDNs (Sucuri WAF included) from caching session-bound responses.
 *
 * Strategy (two layers):
 *  1. Cache-Control: private, no-store — CDNs must not cache "private"
 *     responses; "no-store" additionally tells Sucuri not to keep a copy
 *     even briefly. This is the signal Sucuri actually acts on.
 *  2. Vary: Cookie — RFC 7234 signal for standards-compliant caches.
 *
 * Applied only when the request carries a Laravel session cookie OR the
 * response sets one (i.e. the visitor has a session). Public pages with no
 * cookies (first visit, logged-out crawlers) are left cacheable so Sucuri
 * can still accelerate them.
 */
class AddVaryCookieHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $sessionCookieName = config('session.cookie', 'laravel_session');

        $hasSession = $request->hasCookie($sessionCookieName)
            || $response->headers->has('Set-Cookie');

        if ($hasSession) {
            // "private" prevents CDN caching; "no-store" is the belt-and-
            // suspenders signal that Sucuri documents as triggering a BYPASS.
            $response->headers->set(
                'Cache-Control',
                'private, no-store, no-cache, must-revalidate'
            );
            $response->headers->set('Pragma', 'no-cache');
        }

        // Always add Vary: Cookie for standards-compliant intermediate caches.
        $vary   = $response->headers->get('Vary', '');
        $values = array_filter(array_map('trim', explode(',', $vary)));
        if (!in_array('Cookie', $values, true)) {
            $values[] = 'Cookie';
            $response->headers->set('Vary', implode(', ', $values));
        }

        return $response;
    }
}
