# Auth UI V2 — Login & Register Redesign

**Version:** 2.0
**Date:** 2026-08-31
**Scope:** `dashboard/resources/views/auth/login.blade.php`, `dashboard/resources/views/auth/register.blade.php`
**Design system:** Dashboard bootstrap tokens — Sidebar #0F172A, Primary #2563EB, Success #22C55E, Danger #EF4444, Background #F8FAFC, Cards #FFFFFF, Border #E2E8F0, Bootstrap Icons

## Objectives
- Replace generic Breeze/Tailwind guest layout with dashboard design-system consistent two-panel auth.
- Add project branding and AI surveillance identity.
- Add password visibility toggles and better validation.
- Keep all backend auth behavior unchanged.

## Layout
- Two-panel card: left `brand-panel` (sidebar color #0F172A) + right `form-panel` (white).
- Max width 980-1000px, centered, `border #E2E8F0`, `radius 16px`, `shadow-md`.
- Desktop: `grid 1fr 1fr`; Tablet/Mobile: single column (`@media max-width 767.98px`).
- Background #F8FAFC, Inter font, Bootstrap 5.3 + Icons.

## Login Page
- Brand panel: shield-lock logo, title "AI Classroom / Surveillance Platform", subtitle "Cheating Detection System", description with audit notice, AI surveillance illustration (mock camera feed with person/phone/B1 bounding boxes, 24 FPS badge), feature chips (Person, Phone D2, Orientation B1-B4, Evidence), role list, responsible-use notice.
- Form panel: Home link, heading, `x-auth-session-status`, validation summary, email (with `institutional email only` help), password with `input-group` + eye toggle (`togglePwd()`), remember + forgot link, submit, "Create account" link, lock notice. Keeps `email`, `password`, `remember`, `route('login')` POST, CSRF, `autocomplete` attrs.
- Toggle: button `type=button` swaps `input.type` and `bi-eye` ↔ `bi-eye-slash`, updates `aria-label`.
- Validation: `@error('email'|'password')` with `is-invalid` + `invalid-feedback d-block` with icon; top `alert-danger` when `$errors->any()`.
- Responsive: panels stack, padding 20px on mobile.

## Register Page
- Same two-panel shell.
- Brand panel: same header, secure enrollment mock (role chips), feature list.
- Form fields: `name`, `email`, `password`, `password_confirmation` — preserves Breeze names/route `POST /register`, CSRF, autocomplete.
- Password: toggle button per field (`togglePwd`); strength meter under password.
- Strength meter: 6px track `#E2E8F0` + `.strength-bar` width/color by score. Score: +1 length≥8, +1 mixed case, +1 digit, +1 symbol. Labels: Too weak/Weak/Fair/Good/Strong with colors `#EF4444/#F59E0B/#3B82F6/#22C55E`. Text hint updates live via `checkStrength()`.
- Confirm password: toggle, no meter, hint.
- Spacing: `mb-3` per field, `form-label` 11px uppercase muted, improved hierarchy, `btn-primary` full width.
- Responsive: same breakpoint.

## Accessibility
- One H1 per page, labels with `*` required, `aria-required`, `aria-describedby`, `aria-label` on toggles, focus ring `0 0 0 3px rgba(37,99,235,.15)`, 4.5:1 contrast.

## Backend
- No changes: routes, controllers, validation, tests preserved. Forms `novalidate` to allow server messages.

## Tests
- `tests/Feature/Auth/AuthenticationTest.php` and `RegistrationTest.php` remain passing (status 200, post, assertAuthenticated).

## Files
- `dashboard/resources/views/auth/login.blade.php` — standalone HTML (no `x-guest-layout`).
- `dashboard/resources/views/auth/register.blade.php` — standalone HTML.

## Verification
```
php artisan test --filter=Auth
```
