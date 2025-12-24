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
        'reverb/*',
    ],

    'allowed_methods' => ['*'], // All HTTP methods

    'allowed_origins' => [
        'http://localhost:3000',   // React dev server
        'http://localhost:5173',   // Vite dev server
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'https://owner.iv-erp.com', // Production backend
        'https://aljanoubia.com',   // Production frontend if used
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Total-Count', // if you return custom headers from API
    ],

    'max_age' => 0,

    // Enable credentials if using cookies or auth headers
    'supports_credentials' => true,
];
