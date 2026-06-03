<?php

declare(strict_types=1);

return [

    // Number of OTP digits. 6 for B2C login; raise to 8 for high-assurance purposes.
    'digits' => (int) env('REBEL_OTP_DIGITS', 6),

    // Code validity in seconds (NIST: max 600s = 10 min).
    'ttl_seconds' => (int) env('REBEL_OTP_TTL', 600),

    // Maximum verification attempts per challenge before it is blocked.
    'max_attempts' => (int) env('REBEL_OTP_MAX_ATTEMPTS', 5),

    // Maximum number of resends per challenge.
    'max_resends' => (int) env('REBEL_OTP_MAX_RESENDS', 3),

    // Minimum cooldown (seconds) between two resends.
    'resend_cooldown_seconds' => (int) env('REBEL_OTP_RESEND_COOLDOWN', 30),

    // Atomic verification store: 'redis' (Lua) if available, otherwise 'database' (lock).
    'store' => env('REBEL_OTP_STORE', 'database'),

    // Target time (ms) to normalise the "start" response and avoid timing-enumeration.
    'timing_target_ms' => (int) env('REBEL_OTP_TIMING_TARGET_MS', 250),

    /*
    |--------------------------------------------------------------------------
    | Reference web routes (login/verify/resend)
    |--------------------------------------------------------------------------
    | Enabled by default for an "out-of-box" experience. Disable them if you use
    | your own controllers. The views are publishable (tag rebel-email-otp-views).
    */
    'routes' => [
        'enabled' => (bool) env('REBEL_OTP_ROUTES', true),
        'prefix' => env('REBEL_OTP_ROUTES_PREFIX', 'account/login'),
        'middleware' => ['web'],
    ],

];
