<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells any HTTP cache sitting in front of this app (CDN/WAF) that a
 * response depends on the request's Cookie header, so it must not be
 * shared between different sessions -- e.g. an authenticated page
 * incorrectly cached and served back to a different, or logged-out, user.
 *
 * This is a defensive, standards-based signal (RFC 7234 Vary). It does not
 * replace correctly configuring the upstream cache to bypass session
 * cookies, but well-behaved caches honor it even without that.
 */
class AddVaryCookieHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $vary = $response->headers->get('Vary');
        $values = array_filter(array_map('trim', explode(',', (string) $vary)));

        if (!in_array('Cookie', $values, true)) {
            $values[] = 'Cookie';
            $response->headers->set('Vary', implode(', ', $values));
        }

        return $response;
    }
}
