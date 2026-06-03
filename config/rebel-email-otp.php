<?php

declare(strict_types=1);

return [

    // Numero di cifre dell'OTP. 6 per login B2C; alza a 8 per purpose ad alta assurance.
    'digits' => (int) env('REBEL_OTP_DIGITS', 6),

    // Validità del codice in secondi (NIST: max 600s = 10 min).
    'ttl_seconds' => (int) env('REBEL_OTP_TTL', 600),

    // Tentativi massimi di verifica per challenge prima del blocco.
    'max_attempts' => (int) env('REBEL_OTP_MAX_ATTEMPTS', 5),

    // Numero massimo di reinvii per challenge.
    'max_resends' => (int) env('REBEL_OTP_MAX_RESENDS', 3),

    // Cooldown minimo (secondi) tra due reinvii.
    'resend_cooldown_seconds' => (int) env('REBEL_OTP_RESEND_COOLDOWN', 30),

    // Store di verifica atomica: 'redis' (Lua) se disponibile, altrimenti 'database' (lock).
    'store' => env('REBEL_OTP_STORE', 'database'),

    // Target di tempo (ms) per normalizzare la risposta di "start" ed evitare timing-enumeration.
    'timing_target_ms' => (int) env('REBEL_OTP_TIMING_TARGET_MS', 250),

    /*
    |--------------------------------------------------------------------------
    | Route web di riferimento (login/verify/resend)
    |--------------------------------------------------------------------------
    | Abilitate di default per un'esperienza "out-of-box". Disattivale se usi i
    | tuoi controller. Le viste sono pubblicabili (tag rebel-email-otp-views).
    */
    'routes' => [
        'enabled' => (bool) env('REBEL_OTP_ROUTES', true),
        'prefix' => env('REBEL_OTP_ROUTES_PREFIX', 'account/login'),
        'middleware' => ['web'],
    ],

];
