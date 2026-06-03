# CLAUDE.md — AI working guide for `padosoft/laravel-rebel-email-otp`

> Working on this package with an AI agent (Claude Code, Cursor, Copilot, Codex)? Read this first.
> It's the "batteries" that make vibe-coding here land on the first try. Plain Markdown — every
> tool can read it.

## What this package is
Enterprise passwordless email-OTP login for Laravel Rebel: email → code → access, with real
anti-enumeration, multi-dimensional rate-limiting, multi-tenant/purpose/risk support, atomic
single-use verification and Sanctum token issuance for mobile.

Part of the **Laravel Rebel** suite — an enterprise authentication control plane over Laravel
Fortify. The shared language (value objects, contracts, the audit trail) lives in
`padosoft/laravel-rebel-core`; this package builds on it.

## Non-negotiable conventions
- `declare(strict_types=1);` in every PHP file; `final` classes; constructor property promotion.
- **PHPStan level max** must stay green. Do NOT add `@phpstan-ignore`, baseline entries, or
  `assert()`/inline `@var` to silence errors — fix the root cause. Common recipes:
  - narrow `mixed` before casting: `is_scalar($x) ? (string) $x : null`;
  - `json_decode($s, true)` is `array<array-key, mixed>`;
  - the container's `make('request')` is already typed `Illuminate\Http\Request`;
  - use `cursor()` for large scans, `withoutGlobalScopes()` for cross-tenant admin reads;
  - nested Eloquent `where(fn ($q) => …)` closures receive `Illuminate\Database\Eloquent\Builder`.
- **Tests:** Pest, Testbench. Cover happy path, auth/fail-closed, tenant-scoping, empty state.
- **Style:** Pint (`composer pint`). **Docs/comments in English.**
- Package wiring uses `spatie/laravel-package-tools` (`configurePackage`).

## Security & telemetry rules (suite-wide)
- Never store PII in cleartext: identifiers, IPs and User-Agents are **keyed HMACs** (core
  `KeyedHasher`). Never log OTPs/secrets (the `Redactor` sanitizes audit metadata).
- **Telemetry completeness:** if this package is a channel/driver/bridge/provider, it MUST capture
  everything that fills the admin panel (sends, **delivery receipts**, cost, country, devices,
  anomalies…). Record through the core `AuditLogger` contract — it persists to `rebel_auth_events`
  (never session) and supports **configurable sync|queue** dispatch (Horizon-ready). Skip a field
  only when the driver genuinely can't supply it, and surface an honest empty state — never fake data.

## How to extend it
- **Swap the OTP generation/hashing:** `Otp\NumericOtpGenerator` (CSPRNG, zero-padded codes) and
  `Otp\OtpHasher` (keyed HMAC of `challengeId | code | salt`, constant-time verify) are the seams —
  replace them to change code format/length or the hashing strategy without touching the actions.
- **Provide a `SubjectResolver`:** the shipped `Resolvers\NullSubjectResolver` resolves nobody;
  bind your own (core `SubjectResolver` contract) to map an email identifier → your customer model.
- **Customize the challenge flow / token issuance:** the three actions
  (`Actions\StartEmailOtpChallenge`, `ResendEmailOtpChallenge`, `VerifyEmailOtpChallenge`)
  orchestrate the `Models\EmailOtpChallenge` lifecycle and call the core `TokenIssuer` on success —
  extend these (and the `Enums\ChallengeStatus` states) for new purposes/risk handling; tokens
  themselves come from the host app's `TokenIssuer` binding.
- **Customize delivery:** override `Notifications\EmailOtpNotification` (or its markdown view) to
  rebrand the email; the `Console\PruneChallengesCommand` handles retention.

## Definition of Done (per change)
1. Red→green with Pest; `composer phpstan` (max) + `composer pint -- --test` clean.
2. One feature branch, one PR to `main`. CI matrix **PHP 8.3/8.4/8.5 × Laravel 12/13** must be green.
3. Update `README.md` + `CHANGELOG.md`. Squash-merge.
4. **Release:** `git tag vX.Y.Z && git push origin vX.Y.Z` + `gh release create`. Stay in `0.1.x`
   (Composer `^0.1` excludes `0.2.0` and would break dependents).

## Skills
This repo ships invocable skills under `.claude/skills/` — at least `rebel-package-dev` (the dev
loop + PHPStan-max recipes). Invoke it before non-trivial work.

## Session startup
At the start of each session, in this order:
1. Read `docs/LESSON.md` (accumulated knowledge — applies to you and every subagent).
2. Read `docs/PROGRESS.md` (where we left off).
3. Read `docs/IMPLEMENTATION-PLAN.md` (full plan) and `AGENTS.md` (the complete operational rules:
   branching, Definition of Done, local loop + GitHub gates, guardrails, design-lock).

Key reminders: `copilot` only with `-p` (it blocks otherwise); one PR per macro-task (sub-tasks are
local commits with the local loop: tests + Playwright if UI + local Copilot review); update
`PROGRESS.md` after each sub-task and `LESSON.md` whenever you learn something.
