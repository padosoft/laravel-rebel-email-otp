<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inserisci il codice</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f7f8fa; margin: 0; }
        .rebel-card { max-width: 380px; margin: 8vh auto; background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .rebel-card h1 { font-size: 1.25rem; margin: 0 0 4px; }
        .rebel-card p.muted { color: #718096; margin: 0 0 20px; font-size: .9rem; }
        input[name=code] { width: 100%; box-sizing: border-box; padding: 12px; font-size: 1.5rem;
            letter-spacing: .4em; text-align: center; border: 1px solid #cbd5e0; border-radius: 8px; }
        button { width: 100%; margin-top: 16px; padding: 11px; font-size: 1rem; border: 0;
            border-radius: 8px; background: #2779bd; color: #fff; cursor: pointer; }
        .rebel-row { display: flex; justify-content: space-between; align-items: center; margin-top: 14px;
            font-size: .85rem; }
        .rebel-row a, .rebel-row button.link { color: #2779bd; background: none; border: 0; padding: 0;
            width: auto; margin: 0; cursor: pointer; font-size: .85rem; }
        .rebel-error { color: #cc1f1a; font-size: .85rem; margin-top: 10px; }
        .rebel-muted { color: #718096; }
    </style>
</head>
<body>
    <div class="rebel-card" data-rebel-otp-verify data-resend-cooldown="{{ $cooldown ?? 30 }}">
        <h1>Inserisci il codice</h1>
        <p class="muted">Abbiamo inviato un codice a <strong data-testid="masked-email">{{ $maskedEmail }}</strong>.</p>

        <form method="POST" action="{{ route('rebel-email-otp.verify') }}">
            @csrf
            <input type="hidden" name="challenge_id" value="{{ $challengeId }}">
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   pattern="[0-9]*" maxlength="{{ $digits ?? 6 }}" required autofocus
                   data-rebel-otp-input data-testid="code-input">

            @error('code')
                <div class="rebel-error" data-testid="code-error">{{ $message }}</div>
            @enderror

            <button type="submit" data-testid="verify-submit">Verifica</button>
        </form>

        <div class="rebel-row">
            <a href="{{ route('rebel-email-otp.login') }}" data-testid="change-email">Cambia email</a>

            <form method="POST" action="{{ route('rebel-email-otp.resend') }}">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button type="submit" class="link" data-rebel-resend data-testid="resend">
                    Reinvia il codice <span data-rebel-countdown></span>
                </button>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/laravel-rebel-email-otp/rebel-email-otp.js') }}" defer></script>
</body>
</html>
