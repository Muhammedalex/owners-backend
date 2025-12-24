<?php
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:5173',
        'https://owner.iv-erp.com',
        'https://aljanoubia.com',
        'https://amazingwill.sa',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
    ],

    'allowed_headers' => ['*'],

    'supports_credentials' => true,
];