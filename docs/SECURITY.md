# Security

## Reporting a vulnerability

Please disclose privately to `security@torexlabs.cloud`. We commit to:

- acknowledge within **3 business days**
- propose a remediation plan within **14 calendar days** for critical issues
- credit the reporter in the release notes once a fix is shipped (opt-out
  available)

Please do not file public GitHub issues for security vulnerabilities.

## Defensive controls

- **Secrets at rest.** Project API keys, agent tokens, and user personal
  access tokens are SHA-256 hashed in the database. The plaintext value is
  returned to the caller exactly once at issuance. Two-factor secrets are
  AES-encrypted with the Laravel `APP_KEY`; recovery codes are bcrypt-hashed.
- **TLS everywhere.** Caddy terminates TLS with automatic Let's Encrypt
  certificates. `URL::forceScheme('https')` is enabled in production and HSTS
  is set with `max-age=31536000; includeSubDomains; preload`.
- **Rate limiting.** `agent-register`, `agent`, `login`, and the default `api`
  limiters are enforced via Laravel's RateLimiter facade.
- **Authorization.** Every org-scoped Eloquent model carries the
  `OrganizationScope` global scope so a missing `where` clause cannot leak
  rows. Policies (`ThreatPolicy`, `ProjectPolicy`, `ServerPolicy`,
  `AlertPolicy`) enforce the operations a user may perform on a record.
- **Mass-assignment.** No controller uses `$request->all()` for create/update
  paths. Validation rules whitelist the fields that get persisted.
- **CSRF.** All POST/PUT/DELETE web routes require a CSRF token. The API
  surface uses Sanctum stateful tokens, scoped per origin via
  `SANCTUM_STATEFUL_DOMAINS`.
- **CORS.** Strict allow-list driven by `CORS_ALLOWED_ORIGINS`.
- **Security headers** (set by `SecureHeaders` middleware *and* Caddy):
  `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`,
  `Permissions-Policy`, `Content-Security-Policy` (configurable via
  `CSP_POLICY`), `Cross-Origin-Opener-Policy`, `Cross-Origin-Resource-Policy`.
- **Audit log.** Every mutation to `User`, `Project`, `Server`, `Threat`,
  `Action`, `Agent` is captured by Spatie Activitylog and surfaced at
  `/audit`.
- **2FA.** TOTP enrollment, single-use recovery codes, and a login challenge
  flow are all required for any user with 2FA enabled.

## Threat model

Top-of-mind adversaries:

1. **Attacker who steals an agent token.** They can submit forged threat
   reports and consume the per-agent rate budget, but cannot reach any other
   tenant; the token only authorises one project / server pair. Rotate the
   token (`Dashboard > Project > Agents > Rotate`) to revoke the stolen one.
2. **Cross-tenant data access.** Mitigated by global scopes + policies; tested
   in `tests/Feature/PolicyEnforcementTest.php`.
3. **CSRF / clickjacking.** Mitigated by CSRF middleware, SameSite=Lax cookies,
   and X-Frame-Options: DENY.
4. **Account takeover via password reuse.** Mitigated by 2FA; we recommend
   making 2FA mandatory for any user with admin or superadmin roles.

Out of scope for v1:

- Defending against a compromised root user on a customer host. The agent has
  the same authority as `root` by design (so it can quarantine files), so a
  hostile root user can interfere with it.
- Side-channel attacks on shared hosting environments.

## Cryptographic primitives

- `hash('sha256', $token)` for token storage (preimage resistance is
  sufficient; we do not need work factor since tokens are 32+ char random).
- `password_hash($code, PASSWORD_BCRYPT)` for 2FA recovery codes.
- AES-256-CBC (Laravel `Crypt::encryptString`) for the 2FA secret.
- TLS via Caddy (Let's Encrypt) + the agent's Go TLS stack with hostname
  verification.

## Software supply chain

- Composer + npm packages pinned via `composer.lock` and `package-lock.json`.
- Container base images pinned to specific minor versions (`mysql:8.4`,
  `redis:7-alpine`, `php:8.3-fpm-alpine`).
- Go binaries built by GitHub Actions on `ubuntu-latest` and uploaded with
  a `checksums.txt` so installers can verify integrity.
