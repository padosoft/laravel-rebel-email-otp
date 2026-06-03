# Laravel Rebel — Email OTP

> **Passwordless login via email-OTP, enterprise-grade.** Email → code → access, Shopify-style, but with real anti-enumeration, rate-limiting/abuse protection, multi-tenant support, atomic single-use verification and **Sanctum token** issuance for mobile. Part of the `padosoft/laravel-rebel-*` suite.

<p align="center">
  <img src="resources/screenshoots/Laravel-Rebel-banner.png" alt="Laravel Rebel" width="100%">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12%20%7C%2013-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12|13">
  <img src="https://img.shields.io/badge/PHP-8.3%20%7C%208.4%20%7C%208.5-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/PHPStan-max-2A6FDB?style=flat-square" alt="PHPStan max">
  <img src="https://img.shields.io/badge/tests-Pest%204-22C55E?style=flat-square" alt="Pest 4">
  <img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT">
</p>

---

## Table of contents

- [What it is (and what it is NOT)](#what-it-is-and-what-it-is-not)
- [Quick glossary](#quick-glossary)
- [Why Rebel Email-OTP — the moats](#why-rebel-email-otp--the-moats)
- [Rebel vs the others (card-battle)](#rebel-vs-the-others-card-battle)
- [How it works (step by step)](#how-it-works-step-by-step)
- [Installation (junior-proof)](#installation-junior-proof)
- [Configuration (every option)](#configuration-every-option)
- [Usage examples](#usage-examples)
- [Mobile / Sanctum](#mobile--sanctum)
- ["Live" testing with Mailtrap (real emails)](#live-testing-with-mailtrap-real-emails)
- [Security](#security)
- [Testing & License](#testing--license)

---

## What it is (and what it is NOT)

**It is** the engine that lets a user sign in **without a password**: they enter their email, receive an **OTP code**, type it in, and they are in. On top of that it handles everything a serious product requires: anti-enumeration, rate-limiting, multi-tenant, audit, atomic verification, tokens for mobile apps.

**It is not** an SMS client (for SMS/WhatsApp there is `laravel-rebel-channels`), and it does **not** replace Laravel Fortify (classic login/passkey/TOTP stay with Fortify, orchestrated by `laravel-rebel-bridge-fortify`).

It depends on [`padosoft/laravel-rebel-core`](https://github.com/padosoft/laravel-rebel-core) (shared value objects/contracts). For the **big-picture view of the ecosystem**, start from the core README.

---

## Quick glossary

| Term | In plain words |
|---|---|
| **OTP** | One-time code (e.g. 6 digits) sent via email. |
| **Challenge** | The "case" opened when you request a code: it has an id, an expiry and a number of attempts. |
| **Anti-enumeration** | Not letting an attacker figure out whether an email is registered or not (always the same response). |
| **Single-use / atomic** | A code works **only once**; two parallel verifications cannot both succeed. |
| **Idempotency-Key** | If the mobile app retries the same request (flaky network), you do **not** send two codes. |

---

## Why Rebel Email-OTP — the moats

| ★ | What | In short |
|:--:|---|---|
| ★ | **Real anti-enumeration** | Response + **response time** + size identical for an existing/non-existing email. Most packages reveal whether the account exists. |
| ★ | **Atomic single-use verification** | Pessimistic lock (or Redis Lua): no replay, no race conditions. |
| ★ | **Code never in plaintext** | Stored as an **HMAC** with a per-challenge salt + versioned pepper (rotation without breakage). |
| ★ | **Web + Mobile** | Same flow: web → session; mobile → **Sanctum TokenPair** (access + refresh). |
| ★ | **Multi-tenant & audit** | Per-tenant isolation + audit trail with automatic secret redaction. |

---

## Rebel vs the others (card-battle)

| Feature | Shopify passwordless | `spatie/laravel-one-time-passwords` | Generic "magic link" packages | **Rebel Email-OTP** |
|---|:--:|:--:|:--:|:--:|
| Login email→code | ✅ | ✅ | ✅ | ✅ |
| Real anti-enumeration (msg + **timing** + size) | ⚠️ | ❌ | ❌ | ✅ |
| Atomic single-use verification | ✅ | ✅ | ❌ | ✅ |
| Code stored as HMAC + salt + key rotation | n/a | ❌ | ❌ | ✅ |
| Idempotency-Key (mobile retry) | n/a | ❌ | ❌ | ✅ |
| Single active challenge / resend with cooldown | ✅ | ❌ | ❌ | ✅ |
| Mobile token issuance (Sanctum) | n/a | ❌ | ❌ | ✅ |
| Multi-tenant + audit with redaction | ⚠️ | ❌ | ❌ | ✅ |

**Why it wins:** it is not an "OTP helper", it is a **product engine** with the security properties already baked in.

---

## How it works (step by step)

```text
1) START   the user enters their email
           → Rebel opens a challenge, generates a code, sends it (queued)
           → ALWAYS responds generically (anti-enumeration) + normalized timing
2) VERIFY  the user types the code
           → ATOMIC verification (lock): expired? consumed? too many attempts?
           → if correct: challenge "consumed" (single-use) → login
                web    = session + cookie
                mobile = Sanctum TokenPair (access + refresh)
3) RESEND  (optional) resends with a cooldown and a maximum limit
```

---

## Installation (junior-proof)

**1. Require the package**
```bash
composer require padosoft/laravel-rebel-email-otp
```

**2. Publish the config and views (optional)**
```bash
php artisan vendor:publish --tag=rebel-email-otp-config
php artisan vendor:publish --tag=rebel-email-otp-views     # customize the screens
php artisan vendor:publish --tag=rebel-email-otp-assets    # publish the JS to public/vendor/...
```

**3. Set the core pepper in `.env`** (secret key for the HMACs)
```dotenv
# generate:  php -r "echo bin2hex(random_bytes(32));"
REBEL_PEPPER_V1=paste-a-long-random-value-here
REBEL_PEPPER_CURRENT=1
```

**4. Run the migrations**
```bash
php artisan migrate
```

**5. Configure a mailer** (in production your own SMTP/ESP; in development/testing Mailtrap, see below).

Done: go to `/account/login` (reference routes included) and try the flow. To use **your own** controllers, disable the routes with `REBEL_OTP_ROUTES=false`.

---

## Configuration (every option)

File: `config/rebel-email-otp.php`

| Key | Default | What it does |
|---|---|---|
| `digits` | `6` | Code digits (use `8` for high-assurance actions). |
| `ttl_seconds` | `600` | Code validity (NIST max: 600s = 10 min). |
| `max_attempts` | `5` | Verification attempts before blocking. |
| `max_resends` | `3` | Maximum resends. |
| `resend_cooldown_seconds` | `30` | Minimum wait between two resends. |
| `store` | `database` | `database` (lock) or `redis` (Lua) for atomic verification. |
| `timing_target_ms` | `250` | Time target for the `start` response (anti-timing). `0` = disabled. |
| `routes.enabled` | `true` | Loads the reference web routes. |
| `routes.prefix` | `account/login` | Route prefix. |

---

## Usage examples

**Start + Verify (PHP API)**
```php
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;

$otp = app(RebelEmailOtp::class);
$ctx = SecurityContext::fromRequest($request, app(KeyedHasher::class))->withGuard('customers');

// 1) start (generic response: does not reveal whether the account exists)
$start = $otp->start(EmailIdentifier::from($request->input('email')), 'customer-login', $ctx);

// 2) verify
$result = $otp->verify($start->challengeId, $request->input('code'), $ctx);

if ($result->success) {
    auth('customers')->login($result->subject); // web (mobile: see below)
}
```

**Resend with cooldown**
```php
$resend = $otp->resend(EmailIdentifier::from($email), 'customer-login', $ctx);
// $resend->status === 'cooldown' | 'max_resends' | 'ok'
```

**Idempotency (mobile retry without double sending)**
```php
$otp->start($identifier, 'customer-login', $ctx, idempotencyKey: $request->header('Idempotency-Key'));
```

**Resolving the user (your app)**
```php
use Padosoft\Rebel\Core\Contracts\SubjectResolver;

app()->bind(SubjectResolver::class, MyCustomerResolver::class); // email → customer
// so $result->subject will be your user after verification
```

---

## Mobile / Sanctum

For headless/mobile clients, after `verify()` issue the token pair with your `TokenIssuer` (a Sanctum extension):

```php
if ($result->success && $result->subject !== null) {
    $tokens = app(\Padosoft\Rebel\Core\Contracts\TokenIssuer::class)->issue($result->subject, $ctx);
    return response()->json([
        'access_token'  => $tokens->accessToken,
        'refresh_token' => $tokens->refreshToken,
        'expires_in'    => $tokens->expiresIn,
    ]);
}
```

---

## "Live" testing with Mailtrap (real emails)

To verify that emails actually arrive (even in CI), use **[Mailtrap](https://mailtrap.io)** (free):

1. Create an account → **Email Testing → Inbox** → copy the **SMTP** credentials (or the API key).
2. Put them in `.env` (see `.env.example`): `MAILTRAP_SMTP_*` / `MAILTRAP_APIKEY` / `MAILTRAP_INBOXID`.
3. The tests in the `live` group will hit the real inbox; without credentials they **auto-skip** (offline-safe). In CI the credentials go into the **GitHub Actions secrets**.

> `.env.example` contains **all** the documented variables.

---

## Security

- Code **never in plaintext**: `HMAC(challengeId | code | salt, pepper[version])`, constant-time comparison.
- **Single-use** + atomic verification (lock / Redis Lua).
- **Anti-enumeration**: identical message, **timing** and response size.
- Rate-limiting (attempts/resends) + cooldown; idempotency.
- Audit with automatic **redaction** (never OTP/secrets in the logs).
- For high assurance prefer **passkey/step-up** (email-OTP is AAL1, see `laravel-rebel-step-up`).

---

## Testing & License

```bash
composer test     # Pest
composer phpstan  # max level
composer pint     # style
```

MIT — see [LICENSE](LICENSE). © Padosoft.
