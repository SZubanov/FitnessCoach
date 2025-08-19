<?php

return [
    'token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'cache' => [
        'store' => env('CACHE_DRIVER', 'file'),
        'ttl' => 3600,
    ],
    'conversations' => [
        'ttl' => 86400,
    ],
];