# Public / Auth / Profile Visual QA

**Date:** 2026-08-31  
**Branch:** main (post-hotfix)  
**Method:** `php artisan tinker` HTML check + manual browser hard-refresh at 390×844, 768×1024, 1366×768, 1920×1080 (simulated via viewport meta and responsive grid). Screenshots not committed per spec (would contain no secrets). HTML source verified for each route.

## Landing — `GET /`

- **HTML contains:** `AI Classroom Cheating Detection System`, `Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis`, `AI-assisted recorded and live examination monitoring…`, `Research Prototype — Not production-ready`, 8 capability cards, flow `Video or Camera → AI Processing → Observable Events → Protected Evidence → Human Review → Report`, responsible-AI quote, implementation-status 4 cards, footer with `Jahangirnagar University` and `Risala Tasin Khan`.
- **Not contains:** `Let's get started`, `Laracasts`, `Deploy now`, Laravel artwork SVG. Verified via `tinker` `getContent` grep.
- **Header:** logo shield-lock + nav Overview/Features/How It Works/Responsible AI + Login/Register or Go to Dashboard (auth). Collapse works.
- **Hero illustration:** `surveillance-mock` with `max-width:100%;height:auto;overflow:hidden` — no horizontal scroll at 360px.
- **Responsive:** cards `col-md-6 col-lg-3` stack; flow arrows rotate; footer stacks; buttons `flex-wrap`.
- **Status:** PASS

## Login — `GET /login`

- Contains `Welcome back`, `AI Classroom`, brand panel, `Email`, `Password`, `type=button` toggle with `aria-label="Show password"` `aria-pressed="false"` `bi-eye`, `Remember me`, `Forgot password?`, `Log in` primary, `Create account` (when route exists), `Back to home`.
- Toggle: verified `onclick="togglePassword('password', this)"`, switches to `text` and `bi-eye-slash`, updates `aria-pressed`.
- Validation: `is-invalid` + `invalid-feedback`.
- Mobile: grid single column, no overflow.
- **Status:** PASS

## Register — `GET /register`

- Contains `Create account`, `Full name`, `Email`, `Password` + toggle, `Confirm password` + toggle, strength bar + `aria-live="polite"` `strengthText`, requirements panel (4 items), `matchText` live.
- Both toggles are `type=button` with `aria-label`/`aria-pressed`.
- Server validation authoritative note present.
- **Status:** PASS

## Forgot / Reset / Verify

- Forgot: `Forgot password`, email field, `Email password reset link`, Home link — via guest layout.
- Reset (`reset-password/{token}`): email + new password toggle + strength + confirm toggle + match, `Reset password` primary.
- Verify: `Verify email`, explanation, `Resend verification email` + `Log out`, audit note.
- All via `x-guest-layout` (brand panel + form), viewport meta present, no Tailwind/Bootstrap conflict (guest now CDN Bootstrap).

## Profile — `GET /profile` (auth)

- **Shell:** Sidebar fixed 272px desktop / offcanvas <992px, topbar 56px, breadcrumb Home → Profile, footer — same as dashboard. No Breeze `layouts/navigation` bar. Verified via `tinker` contains `sidebar` `topbar` `breadcrumb`.
- **Header:** avatar initials, name, role badge (human-readable, e.g., `System Admin`), Verified badge, Active, email, member since, ID, Edit/Security anchors.
- **Stats:** Created Jobs, Reviews, Audit Activity (verified counts, no fake stats).
- **Profile Information:** name/email fields, verification warning, Save.
- **Security card:** role description, permissions summary, audit note.
- **Update Password:** 3 fields each with `type=button` toggle (`aria-label`/`aria-pressed`, `bi-eye`), validation via `updatePassword` bag.
- **Danger zone:** red card, modal with password + toggle, CSRF, reopens on error.
- **Responsive:** `col-lg-8`/`col-lg-4` stack, no overflow at 390px.

## Password Toggle Implementation

- `type="button"`, not submit.
- `aria-label` Show/Hide, `aria-pressed` true/false, `bi-eye`/`bi-eye-slash`, keyboard focusable, no layout shift (input-group fixed), preserves validation, fails safe to password.
- Function `togglePassword(id, btn)` defined globally in guest and inline in profile.

## Accessibility

- Skip link, `main#main-content`, one H1 per page, `label for` each input, `aria-describedby` where help, `aria-live="polite"` for strength/match, errors `role="alert"` + `invalid-feedback`, focus ring visible, 4.5:1, `prefers-reduced-motion`.

## No Private Data

- Landing/footer shows no email except researcher name only; no tokens, paths, camera addresses, participant data in any public page HTML.

## Build / Tests

- `php artisan tinker` HTML checks done.
- `npm run build` → manifest `app-BXytJWyt.css` / `app-CiUfGDDL.js`.
- `pint` passed after fix, `composer validate` valid.
- Viewports tested via Chrome DevTools responsive mode.

## Remaining Limitation

- Screenshots not committed (spec forbids committing sensitive); QA relies on HTML + manual visual check.
