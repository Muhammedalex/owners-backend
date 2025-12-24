<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     * 
     * Adds security headers to API responses.
     * These headers protect against various attacks even for API-only applications.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip security headers for API routes to avoid interfering with CORS
      
        $response = $next($request);

        // X-Content-Type-Options: Prevents MIME type sniffing attacks
        // Critical for APIs to prevent browsers from misinterpreting JSON as HTML/JS
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Strict-Transport-Security (HSTS): Forces HTTPS connections
        // Only enable in production with HTTPS
        if (config('app.env') === 'production' && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Referrer-Policy: Controls referrer information sent with requests
        // Protects sensitive data in URLs from being leaked
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Optional: X-Frame-Options (only needed if you serve HTML pages)
        // $response->headers->set('X-Frame-Options', 'DENY');

        // Optional: Content-Security-Policy (only needed if you serve HTML pages)
        // $response->headers->set('Content-Security-Policy', "default-src 'self'");

        return $response;
    }
}
