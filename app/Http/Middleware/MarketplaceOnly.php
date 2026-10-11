<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Routes of the tutor marketplace (1:1 bookings, tutor profiles, wallet payouts, commissions).
 * They are switched off when the platform runs as a private academy (ACADEMY_PRIVATE_MODE=true).
 */
class MarketplaceOnly
{
    public function handle(Request $request, Closure $next, ?string $redirectRoute = null): Response
    {
        if (isPrivateAcademy()) {
            // Landing pages (e.g. the old tutor dashboard) send the user to its academy equivalent instead of a 404.
            if ($redirectRoute && $request->isMethod('GET') && !$request->expectsJson()) {
                return redirect()->route($redirectRoute);
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => __('general.api_url_not_found')], Response::HTTP_NOT_FOUND);
            }
            abort(404);
        }

        return $next($request);
    }
}
