# Privileged Account Deletion Policy

**Date:** 2026-08-31
**Scope:** `DELETE /profile` (ProfileController@destroy), `users.destroy` admin path
**Roles:** `system_admin` is privileged; others are standard.

## Policy

1. **Password confirmation required:** `password` must pass `current_password` rule, in `userDeletion` error bag.
2. **CSRF protection:** Route under `web` middleware; `@csrf` in modal form.
3. **Last system_admin protection:** If `hasRole('system_admin')` and `User::whereHas(roles, name=system_admin)->count() <= 1`, deletion is **blocked** with 302 to `profile.edit` and error in `userDeletion:password`:
   > “Cannot delete the last active System Administrator account. Assign another administrator first.”
4. **Audit:** Both blocked and successful deletions are logged via `AuditHelper::log`:
   - Blocked: `profile.delete_blocked`, result `failure`, metadata `last_system_admin`, actor = auth user.
   - Success: `profile.delete`, result `success`, metadata includes email.
5. **Session invalidation:** After success, `Auth::logout()`, `session()->invalidate()`, `regenerateToken()`.
6. **Referential integrity:** `User` delete relies on FK `nullOnDelete` / `cascadeOnDelete` for related tables; audit logs keep `actor_id nullable` so history is preserved. No cascade delete of `audit_logs` where `actor_id` is the deleted user — rows remain with null actor (per migration).
7. **No self-escalation bypass:** Direct request without valid password still fails validation; role check cannot be bypassed.
8. **Admin-managed roles:** Role changes only via `UserController` gated by `role:system_admin`; profile does not allow self-escalation.

## Non-Privileged Accounts

Deletion allowed after password confirmation; audit still recorded.

## Implementation

```php
if ($user->hasRole('system_admin')) {
    $adminCount = User::whereHas('roles', fn($q)=>$q->where('name','system_admin'))->count();
    if ($adminCount <= 1) {
        AuditHelper::log('profile.delete_blocked', 'user', $user->id, 'failure', ...);
        return back()->withErrors([...], 'userDeletion');
    }
}
AuditHelper::log('profile.delete', 'user', $user->id, 'success', ...);
Auth::logout(); $user->delete(); // session invalidate
```

## Tests

- Blocked when last admin tries to delete (assert redirect, error bag, audit `failure`, user still exists).
- Allowed when ≥2 admins (assert redirect `/`, guest, audit `success`).
- Standard user deletion allowed.
- Wrong password rejected (`userDeletion` errors).

## UI

- Profile danger zone card with red accent, modal confirmation, password input with show/hide toggle (`aria-label`, `aria-pressed`).
- Error shown inside modal; modal reopens on validation failure.

## Limitations

- If `role_user` pivot is manually altered outside app, policy still enforces at request time.
- Historical audit records with `actor_id` of deleted user remain but display “Unknown” in UI — intentional preservation.
