<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class CheckLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localeToSet = 'en';

        if (!empty(session()->get('locale'))) {
           $localeToSet = session()->get('locale');
        } else {
            $localeToSet = setting('_general.default_language') ?? 'en';
        }
        $selectedLang = getTranslatedLanguages($localeToSet);
        if(!empty($selectedLang->rtl)){
            session()->put('rtl', true);
        } else {
            session()->forget('rtl');
        }
        App::setLocale($localeToSet);
        return $next($request);
    }
}
