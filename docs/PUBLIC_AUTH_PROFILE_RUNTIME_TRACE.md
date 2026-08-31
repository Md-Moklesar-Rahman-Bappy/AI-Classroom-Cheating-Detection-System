# Public / Auth / Profile Runtime Trace

**Date:** 2026-08-31
**Branch:** `main` (ahead of origin by 1 at start, commit dd91070)
**Initial status:** `git status` showed 7 staged (welcome, login, register, profile, 3 docs) and ~40 unstaged Modified docs/views. `git log --oneline -5` ended at dd91070 feat(auth-ui).

## Route Definitions

### `dashboard/routes/web.php`
```
GET / → closure return view('welcome')
GET /health/ai → AiServiceClient health
GET /dashboard → DashboardController@index (auth,verified)
resource exam-rooms, exam-sessions, camera-sources, video-assets, analysis-jobs, detection-events, model-versions, audit-logs, users, live (various), settings, help, metrics
GET /profile → ProfileController@edit (auth)
PATCH /profile → ProfileController@update (auth)
DELETE /profile → ProfileController@destroy (auth)
require auth.php
```
### `dashboard/routes/auth.php`
```
guest: GET/POST register, GET/POST login, GET/POST forgot-password, GET/POST reset-password/{token}
auth: GET verify-email, GET verify-email/{id}/{hash}, POST verification-notification, GET/POST confirm-password, PUT password, POST logout
```
### `dashboard/bootstrap/app.php`
- `withRouting(web, commands, health)` + `alias role => RoleMiddleware`
- No custom route caching.

### `dashboard/app/Providers/AppServiceProvider.php`
- Registers policies for VideoAsset and AnalysisJob only. No view composers.

### `php artisan route:list` (93 routes)
Verified exact URIs:
- `GET /` → welcome
- `GET login` → AuthenticatedSessionController@create → view auth.login
- `POST login` → store
- `GET register` → RegisteredUserController@create → view auth.register
- `POST register` → store
- `GET profile` → ProfileController@edit → view profile.edit
- `PATCH profile` → update
- `PUT password` → PasswordController@update
- `DELETE profile` → destroy
- `GET forgot-password` → PasswordResetLinkController@create
- `GET reset-password/{token}` → NewPasswordController@create
- `GET verify-email` → EmailVerificationPromptController

## View Serving

| Route | Controller/View | Layout |
|---|---|---|
| GET / | `view('welcome')` → `welcome.blade.php` | standalone HTML, Bootstrap CDN, no @vite, no Laravel artwork after dd91070 (tinker shows project title, surveillance mock, no “Let’s get started”) |
| GET /login | `AuthenticatedSessionController@create` → `auth/login.blade.php` | **standalone** Bootstrap 5.3 + Icons, Inter, two-panel (brand + form), password toggle `type=button` with aria-label, no `x-guest-layout` (post-dd91070). Pre-dd91070 was `x-guest-layout` (Tailwind). |
| GET /register | `RegisteredUserController@create` → `auth/register.blade.php` | same standalone two-panel, toggles for password + confirmation, strength meter, no guest layout |
| GET /profile | `ProfileController@edit` → `profile/edit.blade.php` | `@extends layouts.bootstrap` (dashboard shell: sidebar fixed 272px, topbar, breadcrumb, footer). Pre-dd91070 used `x-app-layout` (generic Breeze top nav). |
| GET forgot-password | `PasswordResetLinkController@create` → `auth/forgot-password.blade.php` | still `x-guest-layout` (Tailwind, generic) — **not yet redesigned** |
| GET reset-password/{token} | `NewPasswordController@create` → `auth/reset-password.blade.php` | still `x-guest-layout` — **not yet redesigned** |
| GET verify-email | `EmailVerificationPromptController` → `auth/verify-email.blade.php` | still `x-guest-layout` — **not yet redesigned** |

## Why Previous Redesign Did Not Fully Affect Runtime (pre-dd91070 analysis)

- `welcome.blade.php` was default Laravel (fonts.bunny, “Let’s get started”, Laracasts, oversized SVG 440×376, `bg-[#FDFDFC]`). `git show HEAD:welcome` confirmed generic.
- `auth/login` and `auth/register` used `<x-guest-layout>` which rendered `layouts/guest.blade.php` (Tailwind Forms, figtree, `@vite`). No password toggle existed.
- `profile/edit` used `<x-app-layout>` → `layouts/app.blade.php` + `layouts/navigation.blade.php` (Breeze top nav, no sidebar).
- Vite manifest existed but dashboard bootstrap shell (`layouts/bootstrap.blade.php`) used CDN Bootstrap, not Vite; guest pages used Vite Tailwind → framework mismatch, but not cached-route caused miss.
- After dd91070, welcome/login/register/profile were replaced with standalone/bootstrap shells and verified via `tinker` (welcome contains project title, no Laravel artwork; login contains `AI Classroom` and `togglePwd`). Remaining gaps: forgot/reset/verify still Breeze; illustration max-width needed constraint; registration policy undocumented.

## Cached Routes/Views & Vite

- `php artisan optimize:clear` / `view:clear` / `route:clear` / `config:clear` run; `php artisan tinker` now returns new HTML.
- `dashboard/vite.config.js` → inputs `resources/css/app.css`, `resources/js/app.js`; `resources/css/app.css` is Tailwind directives; `package.json` → `vite 7`, `tailwind 3`, `alpine`, `laravel-vite-plugin`. `npm run build` needed to generate manifest; dashboard bootstrap layout does **not** use `@vite` (uses CDN), so auth standalone also CDN — consistent, no conflict if guest layout is unified.
- `layouts/guest.blade.php` still contains `@vite` + Tailwind; will be replaced with project guest layout (CDN Bootstrap) to avoid dual frameworks.

## Registration Policy

- Current `routes/auth.php` exposes `GET/POST register` (guest middleware). Tests `RegistrationTest` expects 200 and creates user with no role. Docs previously stated “No public registration” — inconsistency. Decision deferred to `docs/REGISTRATION_ACCESS_DECISION.md`.

## Verification Commands

```
git status; git branch --show-current; git log --oneline -5
php artisan route:list
php artisan tinker --execute="app(...)->handle(Request::create('/', 'GET'))->getContent()"
php artisan optimize:clear; php artisan view:clear
npm ci; npm run build
```
