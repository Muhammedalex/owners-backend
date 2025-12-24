<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS debug middleware (only in development)
        // This should run first to catch any output buffering or header issues
       
        
        // Exclude refresh_token and ownership_uuid cookies from encryption
        // This allows the cookies to be readable by the server without decryption
        $middleware->encryptCookies(except: [
            'refresh_token',
            'ownership_uuid',
        ]);
        
        // Add security headers to all responses (API and web)
        // $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
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
        //
    })->create();
