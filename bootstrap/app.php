<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude refresh_token and ownership_uuid cookies from encryption
        // This allows the cookies to be readable by the server without decryption
        $middleware->encryptCookies(except: [
            'refresh_token',
            'ownership_uuid',
        ]);
        
        // Ensure CORS middleware runs first (before SecurityHeaders)
        // Explicitly add HandleCors middleware to handle CORS requests
        // This is critical for preflight OPTIONS requests
        $middleware->prepend(HandleCors::class);
        
        // Add security headers to all responses (API and web)
        // This runs after CORS, so it won't interfere with CORS headers
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // Add ownership scope middleware to API routes
        // This middleware should run after authentication
        $middleware->alias([
            'ownership.scope' => \App\Http\Middleware\OwnershipScopeMiddleware::class,
            'locale' => \App\Http\Middleware\LocaleMiddleware::class,
        ]);
        
        // Add locale middleware to all API routes (appended to API group)
        // This ensures all API responses are localized based on:
        // 1. Accept-Language header
        // 2. lang query parameter
        // 3. locale cookie
        // 4. Default locale from config
        $middleware->appendToGroup('api', \App\Http\Middleware\LocaleMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle exceptions for API requests
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Only handle API requests
            if ($request->is('api/*')) {
                $statusCode = 500;
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $statusCode = $e->getStatusCode();
                } elseif ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                    $statusCode = $e->getResponse()->getStatusCode();
                }
                $message = $e->getMessage();
                
                // Handle validation exceptions
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.validation_failed'),
                        'errors' => $e->errors(),
                    ], 422);
                }
                
                // Handle authentication exceptions
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.unauthenticated'),
                    ], 401);
                }
                
                // Handle authorization exceptions
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $message ?: __('messages.unauthorized'),
                    ], 403);
                }
                
                // Handle model not found exceptions
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.not_found'),
                    ], 404);
                }
                
                // Handle general exceptions
                return response()->json([
                    'success' => false,
                    'message' => $message ?: __('messages.server_error'),
                    'error' => config('app.debug') ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ] : null,
                ], $statusCode);
            }
        });
    })->create();
