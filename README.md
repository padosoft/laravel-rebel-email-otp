# Laravel Rebel — Email OTP

> **Login passwordless via email-OTP, enterprise-grade.** Email → codice → accesso, stile Shopify, ma con anti-enumeration vera, rate-limit/abuso, multi-tenant, verifica atomica single-use ed emissione **token Sanctum** per mobile. Fa parte della suite `padosoft/laravel-rebel-*`.

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

## Indice

- [Cos'è (e cosa NON è)](#cosè-e-cosa-non-è)
- [Glossario rapido](#glossario-rapido)
- [Perché Rebel Email-OTP — i moat](#perché-rebel-email-otp--i-moat)
- [Rebel vs gli altri (card-battle)](#rebel-vs-gli-altri-card-battle)
- [Come funziona (il flusso, passo-passo)](#come-funziona-il-flusso-passo-passo)
- [Installazione (a prova di junior)](#installazione-a-prova-di-junior)
- [Configurazione (ogni opzione)](#configurazione-ogni-opzione)
- [Esempi d'uso](#esempi-duso)
- [Mobile / Sanctum](#mobile--sanctum)
- [Test "live" con Mailtrap (email vere)](#test-live-con-mailtrap-email-vere)
- [Sicurezza](#sicurezza)
- [Testing & Licenza](#testing--licenza)

---

## Cos'è (e cosa NON è)

**È** l'engine che fa accedere un utente **senza password**: inserisce l'email, riceve un **codice OTP**, lo digita, è dentro. In più gestisce tutto ciò che un prodotto serio richiede: anti-enumeration, rate-limit, multi-tenant, audit, verifica atomica, token per app mobile.

**Non è** un client SMS (per SMS/WhatsApp c'è `laravel-rebel-channels`), e **non** sostituisce Laravel Fortify (login classico/passkey/TOTP restano a Fortify, orchestrati da `laravel-rebel-bridge-fortify`).

Dipende da [`padosoft/laravel-rebel-core`](https://github.com/padosoft/laravel-rebel-core) (value object/contratti condivisi). Per la **visione d'insieme dell'ecosistema**, parti dal README del core.

---

## Glossario rapido

| Termine | In parole semplici |
|---|---|
| **OTP** | Codice usa-e-getta (es. 6 cifre) inviato via email. |
| **Challenge** | La "pratica" aperta quando chiedi un codice: ha un id, una scadenza, dei tentativi. |
| **Anti-enumeration** | Non far capire a un attaccante se un'email è registrata o no (risposta sempre identica). |
| **Single-use / atomico** | Un codice funziona **una volta sola**; due verifiche in parallelo non passano entrambe. |
| **Idempotency-Key** | Se l'app mobile rimanda la stessa richiesta (rete instabile), **non** invii due codici. |

---

## Perché Rebel Email-OTP — i moat

| ★ | Cosa | In breve |
|:--:|---|---|
| ★ | **Anti-enumeration vera** | Risposta + **tempo di risposta** + dimensione identici per email esistente/inesistente. La maggior parte dei pacchetti svela l'esistenza dell'account. |
| ★ | **Verifica atomica single-use** | Lock pessimistico (o Redis Lua): niente replay, niente race condition. |
| ★ | **Codice mai in chiaro** | Salvato come **HMAC** con salt per-challenge + pepper versionato (rotazione senza rotture). |
| ★ | **Web + Mobile** | Stesso flusso: web → sessione; mobile → **TokenPair Sanctum** (access + refresh). |
| ★ | **Multi-tenant & audit** | Isolamento per tenant + audit trail con redazione automatica dei segreti. |

---

## Rebel vs gli altri (card-battle)

| Feature | Shopify | `spatie/laravel-one-time-passwords` | **Rebel Email-OTP** |
|---|:--:|:--:|:--:|
| Login email→codice | ✅ | ⚠️ base | ✅ |
| Anti-enumeration (msg + **timing** + size) | ⚠️ | ❌ | ✅ |
| Verifica atomica single-use | ✅ | ⚠️ | ✅ |
| Codice come HMAC + salt + key rotation | n/d | ⚠️ | ✅ |
| Idempotency-Key (retry mobile) | n/d | ❌ | ✅ |
| Una sola challenge attiva / resend con cooldown | ✅ | ⚠️ | ✅ |
| Emissione token mobile (Sanctum) | n/d | ❌ | ✅ |
| Multi-tenant + audit con redazione | ⚠️ | ❌ | ✅ |

**Perché vince:** non è un "helper OTP", è un **engine di prodotto** con le proprietà di sicurezza già dentro.

---

## Come funziona (il flusso, passo-passo)

```text
1) START   utente inserisce email
           → Rebel apre una challenge, genera un codice, lo invia (queued)
           → risponde SEMPRE in modo generico (anti-enumeration) + tempo normalizzato
2) VERIFY  utente digita il codice
           → verifica ATOMICA (lock): scaduto? consumato? troppi tentativi?
           → se corretto: challenge "consumata" (single-use) → login
                web    = sessione + cookie
                mobile = TokenPair Sanctum (access + refresh)
3) RESEND  (opzionale) reinvia con cooldown e limite massimo
```

---

## Installazione (a prova di junior)

**1. Richiedi il package**
```bash
composer require padosoft/laravel-rebel-email-otp
```

**2. Pubblica config e viste (opzionale)**
```bash
php artisan vendor:publish --tag=rebel-email-otp-config
php artisan vendor:publish --tag=rebel-email-otp-views     # personalizza le schermate
php artisan vendor:publish --tag=rebel-email-otp-assets    # pubblica il JS in public/vendor/...
```

**3. Imposta il pepper del core nel `.env`** (chiave segreta per gli HMAC)
```dotenv
# genera:  php -r "echo bin2hex(random_bytes(32));"
REBEL_PEPPER_V1=incolla-un-valore-lungo-e-casuale
REBEL_PEPPER_CURRENT=1
```

**4. Esegui le migration**
```bash
php artisan migrate
```

**5. Configura un mailer** (in produzione il tuo SMTP/ESP; in sviluppo/test Mailtrap, vedi sotto).

Fatto: vai su `/account/login` (route di riferimento incluse) e prova il flusso. Per usare i **tuoi** controller, disattiva le route con `REBEL_OTP_ROUTES=false`.

---

## Configurazione (ogni opzione)

File: `config/rebel-email-otp.php`

| Chiave | Default | Cosa fa |
|---|---|---|
| `digits` | `6` | Cifre del codice (usa `8` per azioni ad alta assurance). |
| `ttl_seconds` | `600` | Validità del codice (max NIST: 600s = 10 min). |
| `max_attempts` | `5` | Tentativi di verifica prima del blocco. |
| `max_resends` | `3` | Reinvii massimi. |
| `resend_cooldown_seconds` | `30` | Attesa minima tra due reinvii. |
| `store` | `database` | `database` (lock) o `redis` (Lua) per la verifica atomica. |
| `timing_target_ms` | `250` | Target di tempo per la risposta di `start` (anti-timing). `0` = disattivato. |
| `routes.enabled` | `true` | Carica le route web di riferimento. |
| `routes.prefix` | `account/login` | Prefisso delle route. |

---

## Esempi d'uso

**Start + Verify (PHP API)**
```php
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Identifiers\EmailIdentifier;
use Padosoft\Rebel\EmailOtp\RebelEmailOtp;

$otp = app(RebelEmailOtp::class);
$ctx = SecurityContext::fromRequest($request, app(KeyedHasher::class))->withGuard('customers');

// 1) start (risposta generica: non rivela se l'account esiste)
$start = $otp->start(EmailIdentifier::from($request->input('email')), 'customer-login', $ctx);

// 2) verify
$result = $otp->verify($start->challengeId, $request->input('code'), $ctx);

if ($result->success) {
    auth('customers')->login($result->subject); // web (mobile: vedi sotto)
}
```

**Resend con cooldown**
```php
$resend = $otp->resend(EmailIdentifier::from($email), 'customer-login', $ctx);
// $resend->status === 'cooldown' | 'max_resends' | 'ok'
```

**Idempotency (retry mobile senza doppio invio)**
```php
$otp->start($identifier, 'customer-login', $ctx, idempotencyKey: $request->header('Idempotency-Key'));
```

**Risolvere l'utente (la tua app)**
```php
use Padosoft\Rebel\Core\Contracts\SubjectResolver;

app()->bind(SubjectResolver::class, MioCustomerResolver::class); // email → cliente
// così $result->subject sarà il tuo utente dopo la verifica
```

---

## Mobile / Sanctum

Per i client headless/mobile, dopo `verify()` emetti la coppia di token con il tuo `TokenIssuer` (estensione Sanctum):

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

## Test "live" con Mailtrap (email vere)

Per verificare che le email arrivino davvero (anche in CI), usa **[Mailtrap](https://mailtrap.io)** (free):

1. Crea un account → **Email Testing → Inbox** → copia le credenziali **SMTP** (o l'API key).
2. Mettile nel `.env` (vedi `.env.example`): `MAILTRAP_SMTP_*` / `MAILTRAP_APIKEY` / `MAILTRAP_INBOXID`.
3. I test del gruppo `live` colpiranno l'inbox reale; senza credenziali si **auto-skippano** (offline-safe). In CI le credenziali vanno nei **GitHub Actions secrets**.

> `.env.example` contiene **tutte** le variabili documentate.

---

## Sicurezza

- Codice **mai in chiaro**: `HMAC(challengeId | code | salt, pepper[versione])`, confronto a tempo costante.
- **Single-use** + verifica atomica (lock / Redis Lua).
- **Anti-enumeration**: messaggio, **timing** e dimensione risposta identici.
- Rate-limit (tentativi/resend) + cooldown; idempotency.
- Audit con **redazione** automatica (mai OTP/secret nei log).
- Per assurance alta preferisci **passkey/step-up** (email-OTP è AAL1, vedi `laravel-rebel-step-up`).

---

## Testing & Licenza

```bash
composer test     # Pest
composer phpstan  # livello max
composer pint     # stile
```

MIT — vedi [LICENSE](LICENSE). © Padosoft.
