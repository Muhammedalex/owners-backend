<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        // 'reverb/*',
    ],

    'allowed_methods' => ['*'],

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
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Total-Count',
        'X-Requested-With',
    ],

    'max_age' => 86400,

    // ⚠️ هذا هو السبب! يجب أن يكون true للسماح بالكوكيز
    'supports_credentials' => true,
];