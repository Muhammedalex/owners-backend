<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from header, query parameter, or default
        $locale = $request->header('Accept-Language') 
            ?? $request->query('lang') 
            ?? $request->cookie('locale')
            ?? config('app.locale', 'ar');

        // Validate locale (only ar and en are supported)
        if (!in_array($locale, ['ar', 'en'])) {
            $locale = config('app.locale', 'en');
        }

        // Set locale
        app()->setLocale($locale);

        // Store in request for later use
        $request->merge(['current_locale' => $locale]);

        return $next($request);
    }
}

