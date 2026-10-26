<?php

return [
    'pair_max_attempts' => (int) env('LOGIN_PAIR_MAX_ATTEMPTS', 3),
    'pair_block_seconds' => (int) env('LOGIN_PAIR_BLOCK_SECONDS', 86400),
    'ip_window_seconds' => (int) env('LOGIN_IP_WINDOW_SECONDS', 900),
    'ip_max_failures' => (int) env('LOGIN_IP_MAX_FAILURES', 10),
    'ip_block_seconds' => (int) env('LOGIN_IP_BLOCK_SECONDS', 3600),
];
