# Changelog

Tutte le modifiche rilevanti a `padosoft/laravel-rebel-email-otp` sono documentate qui.
Il formato segue [Keep a Changelog](https://keepachangelog.com/it/1.1.0/) e [SemVer](https://semver.org/lang/it/).

## [Unreleased]

## [0.1.0] - 2026-06-03

### Added
- Engine login passwordless email-OTP su `padosoft/laravel-rebel-core` (VCS finché non su Packagist).
- Migration `rebel_email_otp_challenges` (ULID, `code_hmac`+`code_salt`+`key_version`, `idempotency_key`).
- `NumericOtpGenerator` (CSPRNG), `OtpHasher` (HMAC challengeId|code|salt).
- Azioni: `StartEmailOtpChallenge` (anti-enumeration con timing pad, idempotency, una sola challenge attiva, delayed subject resolution), `VerifyEmailOtpChallenge` (atomico/single-use/replay/blocco), `ResendEmailOtpChallenge` (cooldown/max).
- `EmailOtpNotification` (queued), facade `RebelEmailOtp`, `NullSubjectResolver`.
- UI web: viste pubblicabili `login`/`verify` (Blade + vanilla JS: paste OTP + countdown), controller di riferimento + route opzionali, asset `rebel-email-otp.js`.
- `PruneChallengesCommand` (retention/GDPR).
- 28 test (engine + HTTP web flow), PHPStan max, CI 8.3/8.4/8.5 × Laravel 12/13.
