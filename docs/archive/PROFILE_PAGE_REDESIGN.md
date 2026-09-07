# Profile Page Redesign

**Version:** 1.0
**Date:** 2026-08-31
**File:** `dashboard/resources/views/profile/edit.blade.php` (extends `layouts/bootstrap.blade.php`)
**Route:** `GET /profile` (auth)
**Design system:** Dashboard bootstrap — Sidebar #0F172A, Primary #2563EB, Success #22C55E, Danger #EF4444, Background #F8FAFC, Cards #FFFFFF, Border #E2E8F0

## Goals
- Replace default Breeze `x-app-layout` profile with dashboard-consistent layout.
- Header with identity, statistics, and security overview.
- Preserve all Breeze functionality (update info, password, delete) + add show/hide.

## Layout
- `@extends("layouts.bootstrap")` — sidebar, topbar, breadcrumb, footer, AI notice.
- Top header row: title + "Back to dashboard".
- Alerts: `profile-updated` / `password-updated` success.
- Profile header card: gradient bar `#0F172A→#1e293b→#1D4ED8`, avatar 72px circle `#2563EB` with initial, name, role badge `#DBEAFE/#1D4ED8`, Verified/Unverified badge, Active badge, email, member since, ID, Edit/Security anchors.
- Statistics row: 3 KPI cards (Created Jobs, Reviews, Audit Activity) with icon 36px (DBEAFE/2563EB, FEF3C7/D97706, DCFCE7/16A34A), values from `AnalysisJob where created_by`, `ReviewDecision where reviewed_by`, `AuditLog where actor_id`.
- Two-column body: left `col-lg-8` (Profile Info, Security), right `col-lg-4` (Update Password, Delete).

## Cards

### 1. Profile Information Card
- Header: "Profile Information" + Editable badge.
- Description: re-verification note.
- Hidden `send-verification` form.
- Form `PATCH /profile` with `name`, `email` (keeps old values, `is-invalid` + icons on error), verification warning + resend button + `verification-link-sent` success.
- Save button + Saved. text.

### 2. Security Card
- Grid 2×2: Email verification status, Password last updated (`diffForHumans`), Role/description, Audit notice.

### 3. Update Password Card
- Header: "Update Password".
- Form `PUT /password` with three fields: `current_password`, `password`, `password_confirmation` — each `input-group` with eye toggle (`togglePwd`), `@error bag updatePassword`.
- Show/hide controls: `type=button`, `bi-eye ↔ bi-eye-slash`, `aria-label`.
- Submit + Saved. on `password-updated`.

### 4. Delete Account Card
- `border-danger`, warning text, "Delete Account" button triggers Bootstrap 5 modal `#confirmDeleteModal`.
- Modal: title, description, password input with toggle, `userDeletion` error, Cancel + Delete ( `DELETE /profile` ).

## Interactions
- `togglePwd(id,btn)` — swaps type, icon; reused for all password fields including modal.
- Modal auto-show when `$errors->userDeletion->isNotEmpty()` via `DOMContentLoaded` + `bootstrap.Modal`.
- Anchors `#profile-info` / `#security` for quick nav.

## Styling
- Cards: `border #E2E8F0`, `radius 12px`, `shadow-sm`, header white + border-bottom.
- Labels: 11px uppercase muted 600.
- Buttons: primary `#2563EB`, danger `#EF4444`, focus ring `0 0 0 3px rgba(37,99,235,.15)`.
- Background `#F8FAFC`, surface white.
- Responsive: statistics `col-12 col-md-4`, body stacks on <992px.

## Backend Preservation
- No controller change. `ProfileController@edit` passes `$user`; `update`/`destroy` and `password.update` routes kept.
- Validation messages unchanged; error bags `default`, `updatePassword`, `userDeletion` preserved.

## Accessibility
- Extension provides skip link, one H1 (via topbar yield), label + required `*`, focus ring, color+text badges, modal focus trap via Bootstrap.

## Tests
- `tests/Feature/ProfileTest.php` — page 200, update, email verification unchanged, delete with correct/wrong password — all still pass.

## Verification
```
php artisan test --filter=Profile
GET /profile as auth → 200, header shows name/role, toggles functional, modal opens.
```

