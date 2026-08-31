# Profile UI

**File:** `dashboard/resources/views/profile/edit.blade.php` extends `layouts/bootstrap.blade.php`  
**Route:** `GET /profile` (auth), `PATCH /profile`, `PUT /password`, `DELETE /profile`  
**Shell:** Dashboard sidebar (fixed 272px desktop, offcanvas <992px), topbar 56px, breadcrumb, footer, AI notice — same as Dashboard/Video Assets.

## Header Card

- Gradient bar `linear-gradient(135deg,#0F172A,#1e293b,#1D4ED8)`, avatar 72px circle `#2563EB` with initial, negative margin -40px, white border, shadow.
- Name (H3 18px 700), role badge `#DBEAFE/#1D4ED8` with `ucwords(str_replace('_',' ', $roleName))` human-readable, Verified/Unverified badge, Active badge, email, `Member since M d, Y · ID #id`, Edit/Security anchors.
- No fake last-login (not stored).
- Stats row (3 cards):
  - Created Jobs = `AnalysisJob::where('created_by', $user->id)->count()` (verified FK)
  - Reviews = `ReviewDecision::where('reviewed_by', $user->id)->count()`
  - Audit Activity = `AuditLog::where('actor_id', $user->id)->count()`
  - Only verified relations; no invented stats.

## Profile Information Card

- Header `Profile Information` + Editable badge.
- Text: re-verification note.
- Hidden `send-verification` form.
- Form `PATCH /profile`: `name`, `email` (is-invalid + icon on error), verification warning with resend + success if `verification-link-sent`.
- Save + `Saved.` inline on `profile-updated`.

## Security / Role Card

- Grid 2×2: Email verification (Verified on date / Not verified), Password last updated `diffForHumans`, Role description, Audit notice.
- Text: “Role changes are managed by an administrator” — no self-escalation UI.

## Update Password Card

- Header `Update Password`.
- Form `PUT /password`: `current_password`, `password` (new), `password_confirmation` — each `input-group` + button `type=button` `aria-label="Show password"` `aria-pressed="false"` `onclick="togglePwd('id', this)"` + `bi-eye` (→ `bi-eye-slash`), `autocomplete` correct, `@error` bag `updatePassword`.
- Submit Save + Saved on `password-updated`.
- JS `togglePwd` updates `aria-label` and `aria-pressed`, preserves validation, fails safe to password.

## Danger Zone

- Card `border-danger`, red accent header, text about permanent deletion.
- Button triggers modal `#confirmDeleteModal` (Bootstrap 5).
- Modal: title, consequence text, `password` input + toggle (aria-label, aria-pressed), `userDeletion` error, Cancel + Delete.
- CSRF `@csrf`, `@method('delete')`.
- JS reopens modal if `$errors->userDeletion->isNotEmpty()`.

## Privileged Protection

- Controller blocks last `system_admin` deletion, logs `profile.delete_blocked` failure, returns withErrors to `userDeletion`.

## Layout Details

- Cards: border #E2E8F0, radius 12px, shadow-sm, header white, padding 16–20px.
- Labels 11px uppercase muted 600, inputs 38px, focus ring `0 0 0 3px rgba(37,99,235,.15)`.
- Background #F8FAFC, surface white.
- Responsive: `col-lg-8`/`col-lg-4` stack <992px, header flex-wrap, no overflow.

## Accessibility

- Bootstrap shell provides skip link, `main#main-content`, one H1 via topbar yield, label `for` each input, error `role=alert`, toggle `aria-pressed` + `aria-label`, keyboard accessible, modal focus trap via Bootstrap, `prefers-reduced-motion` respected.

## Backend

- No change to `ProfileUpdateRequest`, `PasswordController`. `ProfileController@destroy` adds last-admin guard + audit.
