# Changelog

All notable changes to `padosoft/laravel-rebel-email-otp` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and [SemVer](https://semver.org/).

## [Unreleased]

## [0.1.0] - 2026-06-03

### Added
- Passwordless email-OTP login engine on top of `padosoft/laravel-rebel-core` (VCS until it lands on Packagist).
- `rebel_email_otp_challenges` migration (ULID, `code_hmac`+`code_salt`+`key_version`, `idempotency_key`).
- `NumericOtpGenerator` (CSPRNG), `OtpHasher` (HMAC challengeId|code|salt).
- Actions: `StartEmailOtpChallenge` (anti-enumeration with timing pad, idempotency, single active challenge, delayed subject resolution), `VerifyEmailOtpChallenge` (atomic/single-use/replay/blocking), `ResendEmailOtpChallenge` (cooldown/max).
- `EmailOtpNotification` (queued), `RebelEmailOtp` facade, `NullSubjectResolver`.
- Web UI: publishable `login`/`verify` views (Blade + vanilla JS: OTP paste + countdown), reference controller + optional routes, `rebel-email-otp.js` asset.
- `PruneChallengesCommand` (retention/GDPR).
- 28 tests (engine + HTTP web flow), PHPStan max, CI 8.3/8.4/8.5 × Laravel 12/13.
