<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accedi</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f7f8fa; margin: 0; }
        .rebel-card { max-width: 380px; margin: 8vh auto; background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .rebel-card h1 { font-size: 1.25rem; margin: 0 0 4px; }
        .rebel-card p.muted { color: #718096; margin: 0 0 20px; font-size: .9rem; }
        label { display: block; font-size: .85rem; margin-bottom: 6px; color: #1a202c; }
        input[type=email] { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 1rem;
            border: 1px solid #cbd5e0; border-radius: 8px; }
        button { width: 100%; margin-top: 16px; padding: 11px; font-size: 1rem; border: 0;
            border-radius: 8px; background: #2779bd; color: #fff; cursor: pointer; }
        button:disabled { opacity: .6; cursor: not-allowed; }
        .rebel-error { color: #cc1f1a; font-size: .85rem; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="rebel-card">
        <h1>Accedi</h1>
        <p class="muted">Inserisci la tua email: ti invieremo un codice di accesso.</p>

        <form method="POST" action="{{ route('rebel-email-otp.start') }}" data-rebel-otp-start>
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" autocomplete="email" inputmode="email"
                   required autofocus value="{{ old('email') }}">

            @error('email')
                <div class="rebel-error" data-testid="email-error">{{ $message }}</div>
            @enderror

            <button type="submit" data-rebel-submit>Invia codice</button>
        </form>
    </div>
</body>
</html>
