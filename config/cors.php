<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        // Note: Reverb WebSocket has its own CORS config in config/reverb.php
        // Reverb runs on a separate port (usually 8080) and doesn't use Laravel's CORS middleware
    ],

    'allowed_methods' => ['*'], // All HTTP methods

    'allowed_origins' => array_filter([
        // Development origins
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        // Production origins
        'https://amazingwill.sa',
        'https://www.amazingwill.sa',
        // Production origins from environment
        env('FRONTEND_URL'),
        env('FRONTEND_URL_ALT'),
        // Fallback production origins
        'https://owner.iv-erp.com',
        'https://aljanoubia.com',
    ]),

    'allowed_origins_patterns' => [
        // Allow localhost with any port
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'Origin',
        'Cache-Control',
        'Pragma',
        'Accept-Language',
        'Accept-Encoding',
    ],

    'exposed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Total-Count',
        'X-Requested-With',
    ],

    'max_age' => 86400, // Cache preflight for 24 hours

    // Enable credentials if using cookies or auth headers
    'supports_credentials' => true,
];
