<?php

return [
    'ai_service' => [
        'base_url' => env('AI_SERVICE_BASE_URL', 'http://127.0.0.1:8001'),
        'token' => env('AI_SERVICE_TOKEN', 'dev-token-change-me'),
        'timeout' => (int) env('AI_SERVICE_TIMEOUT', 10),
        'connect_timeout' => (int) env('AI_SERVICE_CONNECT_TIMEOUT', 5),
        'retry_attempts' => (int) env('AI_SERVICE_RETRY_ATTEMPTS', 2),
        'retry_delay_ms' => (int) env('AI_SERVICE_RETRY_DELAY_MS', 200),
    ],
];
