<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        // Not in maintenance mode
        if (!$this->isUnderMaintenance()) {
            return $next($request);
        }

        // Los administradores ven el sitio normal para poder revisar los cambios.
        if ($request->user()?->hasRole('admin')) {
            return $next($request);
        }

        if ($this->isUnderMaintenance() &&  $request->route()->getName() == 'pagebuilder.page' && $request->get('preview') != 'yes') {
            return response()->view('errors.503', [], 503)->header('Retry-After', '3600');
        }

        $excludedRoutes = [
            'login',
            'admin.',
            'optionbuilder',
            'pagebuilder',
            'page.'
        ];

        if ($request->route() && collect($excludedRoutes)->contains(function ($excludedRoute) use ($request) {
            return Str::startsWith($request->route()->getName(), $excludedRoute);
        })) {
            return $next($request);
        }

        return response()->view('errors.503', [], 503)->header('Retry-After', '3600');
    }

    protected function isUnderMaintenance()
    {
        return setting('_maintenance.maintenance_mode') == 'Yes';
    }
}
