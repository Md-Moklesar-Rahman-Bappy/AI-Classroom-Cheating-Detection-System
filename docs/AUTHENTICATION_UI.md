# Authentication UI

**Scope:** `layouts/guest.blade.php`, `auth/login.blade.php`, `auth/register.blade.php`, `auth/forgot-password.blade.php`, `auth/reset-password.blade.php`, `auth/verify-email.blade.php`, `auth/confirm-password.blade.php`  
**Design:** Guest two-panel (left brand panel #0F172A, right card white, BG #F8FAFC, Primary #2563EB, Border #E2E8F0), Bootstrap 5.3 + Icons, Inter.

## Guest Layout (`layouts/guest.blade.php`)

- **Head:** `viewport`, `csrf-token`, `title` from config, Bootstrap + Icons CDN, Inter, tokens CSS.
- **Structure:** `skip-link`, `main.guest-wrap` grid `1fr 1fr` → `@media 767px` single column, brand panel + form panel.
- **Brand panel:** logo (shield-lock), title `AI Classroom / Surveillance Platform`, H1 `AI Classroom Cheating Detection System`, description `AI-assisted recorded and live... Research prototype`, illustration mock (max-width 100%, 130px gradient, bbox overlays), chip row (Person, Phone D2, Orientation, Evidence), capability list (encrypted, roles, protected evidence), responsible AI notice + Home link.
- **Form panel:** `{{ $slot }}` with white background.
- **JS:** Global `togglePassword(inputId, button)` — switches `type`, icon `bi-eye ↔ bi-eye-slash`, `aria-label` Show/Hide, `aria-pressed`.
- **Constraint:** `.illustration, .surveillance-inner {max-width:100%; height:auto; overflow:hidden}`.

## Login (`auth/login.blade.php`)

- Extends `x-guest-layout`.
- Heading `Welcome back` (H2), Home link.
- Fields: `email` (type email, required, autocomplete username, aria-describedby, help “Institutional email”), `password` (type password, required, autocomplete current-password) with `input-group` + button `type=button` (aria-label Show password, aria-pressed false, `onclick="togglePassword('password', this)"`, eye icon), `remember` checkbox, `Forgot password?` link.
- Validation: top alert if `$errors->any()`, field `is-invalid` + `invalid-feedback d-block` with icon, `x-auth-session-status`.
- Submit: `Log in` primary, `Create account` link only if `Route::has('register')` else admin notice.
- Accessibility: labels `for`, `aria-required`, `aria-describedby`, button not submitting, keyboard accessible, `prefers-reduced-motion`.

## Register (`auth/register.blade.php`)

- H2 `Create account`, prototype notice.
- Fields: `name`, `email`, `password`, `password_confirmation` — all required, correct autocomplete, `old()` values.
- **Password:** toggle (Show/Hide, aria-pressed), help, strength meter (6px track, bar width/color by score), `strengthText` aria-live polite, requirements panel (Minimum 8, upper/lower, number, symbol) with live check-circle updates.
- **Confirm:** toggle, `matchText` aria-live polite.
- Strength: score on length≥8, mixed case, digit, symbol → 0-100% colors `#E2E8F0/#EF4444/#F59E0B/#3B82F6/#16A34A` labels Too weak…Strong. Client guidance only, server authoritative (`Rules\Password::defaults`).
- No extra institution/role fields (no DB columns).
- Login link, notice “No privileged role · verification required”.

## Forgot Password (`auth/forgot-password.blade.php`)

- H2 `Forgot password`, Back to login, description.
- Field `email` only, help, submit `Email password reset link`, Home link.
- Uses same guest layout.

## Reset Password (`auth/reset-password.blade.php`)

- H2 `Reset password`, hidden `token`, fields `email`, `password` (new) + toggle + strength + aria-live, `password_confirmation` + toggle + match live.
- Submit `Reset password`.

## Verify Email (`auth/verify-email.blade.php`)

- H2 `Verify email`, explanation why verification required, success alert if `verification-link-sent`, card “Why verification”, buttons Resend (POST `verification.send`) and Log out, note about expiry.

## Confirm Password (`auth/confirm-password.blade.php`)

- H2 `Confirm password`, secure area note, password + toggle, Confirm button.

## Backend

- No change to `RegisteredUserController`, `AuthenticatedSessionController`, `PasswordResetLinkController`, `NewPasswordController`, `VerifyEmailController`.
- Validation remains server truth; client indicators do not log or transmit elsewhere.
- `Route::has('register')` gates UI per registration policy.

## Responsive

- Guest grid collapses at 767px, padding 20px, no horizontal overflow, buttons `w-100` on mobile via Bootstrap.
