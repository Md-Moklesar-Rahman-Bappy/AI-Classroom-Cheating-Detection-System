# Registration Access Decision

**Date:** 2026-08-31
**Status:** Decided — Option B (Research Prototype Enabled)

## Context

- Previous documentation stated “No unrestricted public registration” — accounts created by administrator.
- However runtime routes expose `GET/POST /register` (guest middleware) and landing/login pages link to Register.
- Test suite `RegistrationTest` expects registration to succeed and auto-login.
- No environment-based gate was present.

## Decision

**Option B selected: Registration remains enabled for research prototype / local operation, with safeguards and explicit production recommendation to disable.**

### Rationale

- Repository is a thesis/research prototype, not hardened production.
- Evaluators need self-service registration for local testing without admin provisioning.
- Silent removal would break existing tests and local workflows.
- Policy must be explicit, not assumed.

### Safeguards Implemented

1. **No privileged role on registration:** `RegisteredUserController::store` creates user with no role assignment. User receives no `system_admin`/`exam_admin` automatically. Protected modules (`users`, `audit-logs` role middleware) deny access until administrator assigns least-privilege role via `users` UI.
2. **Email verification required:** User model implements `MustVerifyEmail` check in `ProfileController` — unverified users see resend banner; `auth` + `verified` middleware on dashboard blocks unverified access.
3. **UI labeling:** Landing and guest panels label registration as “Research prototype — pending administrator approval” and state that privileged access requires approval.
4. **Validation authoritative:** Server rules (8 chars, letters, numbers, etc.) enforced; client strength meter is guidance only.
5. **Audit:** Registration already creates audit via User creation; future hardening can add explicit `audit_logs` entry.

### Production Recommendation

For production/institutional deployment:
- Disable `GET/POST register` routes (remove from `routes/auth.php` or gate via `config('app.allow_registration')` env flag).
- Remove Register CTA from landing and login.
- Show “Accounts are created by an authorized administrator” notice (already in guest layout fallback when route missing).
- Ensure at least one active `system_admin` exists.

### Verification

- `php artisan route:list | grep register` shows routes (prototype mode).
- `GET /register` renders 200 with project branding and toggles.
- `POST /register` creates user with `email_verified_at = null`, no roles.
- Protected `GET /users` as new user → 403.

### References

- `dashboard/routes/auth.php` (guest register routes)
- `app/Http/Controllers/Auth/RegisteredUserController.php` (role assignment)
- `docs/THREAT_MODEL.md` (registration abuse threat)
- `docs/SECURITY_AUDIT.md` (public registration review)
