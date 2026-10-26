<?php

return [
    // Pair policy
    'pair_max_attempts' => (int) env('LOGIN_PAIR_MAX_ATTEMPTS', 3),
    'pair_initial_block_seconds' => (int) env('LOGIN_PAIR_INITIAL_BLOCK_SECONDS', 900),
    'pair_escalation_one_seconds' => (int) env('LOGIN_PAIR_ESCALATION_ONE_SECONDS', 3600),
    'pair_escalation_two_seconds' => (int) env('LOGIN_PAIR_ESCALATION_TWO_SECONDS', 21600),
    'pair_max_block_seconds' => (int) env('LOGIN_PAIR_MAX_BLOCK_SECONDS', 86400),

    // IP-wide policy
    'ip_window_seconds' => (int) env('LOGIN_IP_WINDOW_SECONDS', 900),
    'ip_max_failures' => (int) env('LOGIN_IP_MAX_FAILURES', 10),
    'ip_initial_block_seconds' => (int) env('LOGIN_IP_INITIAL_BLOCK_SECONDS', 3600),
    'ip_max_block_seconds' => (int) env('LOGIN_IP_MAX_BLOCK_SECONDS', 86400),

    // Password reset
    'password_reset_account_limit' => (int) env('PASSWORD_RESET_ACCOUNT_LIMIT', 3),
    'password_reset_ip_limit' => (int) env('PASSWORD_RESET_IP_LIMIT', 10),
    'password_reset_window_seconds' => (int) env('PASSWORD_RESET_WINDOW_SECONDS', 3600),

    // MFA (not implemented; kept for future)
    'mfa_max_attempts' => (int) env('MFA_MAX_ATTEMPTS', 5),
    'mfa_block_seconds' => (int) env('MFA_BLOCK_SECONDS', 900),
];
