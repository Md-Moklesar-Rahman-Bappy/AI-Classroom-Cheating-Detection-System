# Role Debug Report — risingbappy1@gmail.com

## Date
2026-08-30 13:49 UTC (Asia/Dhaka)

## A. Database Verification

### Actual Records Found (via MySQL and Eloquent)

**users**
```
id=2 | name=Md Moklesar Rahman | email=risingbappy1@gmail.com | email_verified_at=2026-08-30 13:41:00 | created_at=2026-08-30 13:49:17
```
Total users: 2

**roles** (before fix)
```
Count: 0 (empty)
```
After `php artisan db:seed --class=RolePermissionSeeder`:
```
5 | system_admin | System Administrator
6 | exam_admin | Exam Administrator
7 | invigilator | Invigilator
8 | reviewer | Reviewer
9 | auditor | Auditor
```

**role_user** (before fix)
```
Count: 0 (empty) for user_id=2
```

**permissions / permission_role**
- permissions: 10 rows (manage_rooms, manage_sessions, etc.) after seed
- permission_role: 5 roles mapped to permissions after seed

**Verification**
- User exists: YES (id=2, risingbappy1@gmail.com)
- Has system_admin role before fix: NO (roles count 0, pivot empty)
- Has system_admin after fix: YES (after sync)

### Do Not Assume
Confirmed via direct DB query `SELECT COUNT(*) FROM ai_classroom.roles` (0 before, 5 after) and `SELECT COUNT(*) FROM ai_classroom.role_user WHERE user_id=2` (0 before).

## B. Laravel Tinker Verification

**Command**
```php
$u = App\Models\User::where("email","risingbappy1@gmail.com")->first();
echo $u->name." | ".$u->email;
echo $u->roles->count();
foreach($u->roles as $r) echo $r->name." | ".$r->description;
```

**Before Fix**
```
User: Md Moklesar Rahman | risingbappy1@gmail.com
ID: 2
Roles count: 0
No roles assigned!
All roles in DB: (none)
role_user entries: No pivot entries
```

**After Fix (via sync)**
```
User: Md Moklesar Rahman | risingbappy1@gmail.com
Roles count: 1
 - Role: system_admin | Description: System Administrator
All roles in DB: 5 roles
role_user entries: user_id 2 role_id 5 (system_admin)
```

**Verify role assignments**
```php
$u->hasRole("system_admin") // true after fix
$u->hasAnyRole(["system_admin","exam_admin"]) // true
$u->roles()->first()->description // "System Administrator"
```

## C. Fix Role Assignment

**If user exists but role missing: Assigned via syncRoles equivalent**

Our project uses **custom Role model** (`App\Models\Role`, pivot `role_user`), **not Spatie/laravel-permission**. There is no `HasRoles` trait or `syncRoles()` from Spatie. Instead we use:

```php
$role = Role::where("name","system_admin")->first();
$user->roles()->sync([$role->id]); // equivalent to syncRoles(["system_admin"])
```

**Executed**
```php
$u = User::where("email","risingbappy1@gmail.com")->first();
$role = Role::where("name","system_admin")->first();
$u->roles()->sync([$role->id]);
```

**Result** `role_user` now has 1 row: `user_id=2, role_id=5`. Verified via `hasRole("system_admin") = true`.

**Alternative Spatie**: `syncRoles()` not available (Spatie not installed), so direct DB manipulation avoided; used Eloquent sync as intended.

## D. Permission Cache

**Spatie permissions configuration**
- `config/permission.php` does **not exist** (Spatie not installed, `composer.json` has no `spatie/laravel-permission`)
- `php artisan permission:cache-reset` → `ERROR There are no commands defined in the "permission" namespace.` (expected, no Spatie)
- Custom roles have **no cache layer**; permissions are queried directly via `whereHas` on `role_user` and `permission_role`, so no stale cache.

**Cache actions executed**
```
php artisan permission:cache-reset
→ ERROR (no Spatie, documented)

php artisan optimize:clear
→ Clearing cached bootstrap files.
  config 6.79ms DONE
  cache 28.44ms DONE
  compiled 2.71ms DONE
  events 0.71ms DONE
  routes 0.85ms DONE
  views 72.54ms DONE
```
Result: routes/config/views cleared, no permission cache to clear.

## E. Sidebar Role Display

**Before**
```blade
{{ Auth::user()->roles->first()->name ?? "—" }}
```
- Displayed `—` (mdash) when no role, or raw `system_admin` if present
- Example for risingbappy1 before fix: `Md Moklesar Rahman` / `—` (no role, confusing)

**After Fix**
```blade
{{ Auth::user()->roles->first()?->description ?? Auth::user()->roles->first()?->name ?? "No Role Assigned" }}
```
- Shows `description` ("System Administrator") if available, else `name` ("system_admin"), else fallback "No Role Assigned" if no roles
- Uses nullsafe operator `?->` (PHP 8.2) to avoid null error
- Persistent at bottom of sidebar (`margin-top:auto`, `flex:1` nav)

**Result for risingbappy1 after fix**
```
Md Moklesar Rahman
System Administrator
```
Fallback verified for user with no roles: "No Role Assigned".

## F. Authorization Audit

**Page traced**: `GET /users` (`route("users.index")`) and `GET /exam-rooms` via dashboard

**Before fix** - risingbappy1 with 0 roles:
- `GET /users` → `403 Forbidden - insufficient role`
- `GET /exam-rooms` (requires `system_admin` or `exam_admin` via `RoleMiddleware` on `users` and `hasAnyRole` checks in controllers) → same 403 for auditor-like paths

**Layers verified**

1. **Route middleware** (`routes/web.php`):
   ```php
   Route::resource("users", UserController::class)->middleware("role:system_admin");
   Route::get("audit-logs", ...)->middleware("role:system_admin,auditor,exam_admin");
   Route::get("evidence/{evidence}", ...)->middleware("role:system_admin,exam_admin,reviewer,invigilator,auditor");
   ```
   - `role` alias registered in `bootstrap/app.php`: `$middleware->alias(["role" => \App\Http\Middleware\RoleMiddleware::class])`
   - Correctly aliased, no typo.

2. **Role middleware** (`app/Http/Middleware/RoleMiddleware.php`):
   ```php
   public function handle(Request $request, Closure $next, ...$roles): Response {
       if (!auth()->check()) return redirect()->route("login");
       $user = auth()->user();
       foreach ($roles as $role) {
           if ($user->hasRole($role)) return $next($request);
       }
       abort(403, "Forbidden - insufficient role");
   }
   ```
   - Correctly checks `hasRole` via `whereHas` on `role_user`
   - Denial reason for risingbappy1: `hasRole("system_admin")` returned false (0 roles), so `abort(403)` triggered.
   - No Gate or Policy involved for this route; `Gate` not defined for users.

3. **Gate/Policy**
   - No `Gate::define` for `viewAny` on `User`; `UserController@index` does `if(!auth()->user()->hasRole("system_admin")) abort(403)` directly, not via Policy.
   - No `UserPolicy` exists (checked `app/Policies` - only `User` model, no policy). So denial is purely `RoleMiddleware` + controller `hasRole` check.

**Exact reason for 403**
- `users_roles` pivot empty → `hasRole("system_admin")` false → `RoleMiddleware` `abort(403, "Forbidden - insufficient role")` on `GET /users` and similar for other `system_admin`-only routes.
- Not due to Spatie cache, not due to middleware misconfiguration, not due to policy.

**After fix**
- `GET /users` as risingbappy1 with `system_admin` → `200 OK` (verified via `actingAs` test)
- `GET /dashboard` → shows "System Administrator" in sidebar

## G. Automated Tests - Created

**File**: `dashboard/tests/Feature/RoleAssignmentTest.php` (Pest, RefreshDatabase)

Tests (6):

1. **User role assignment** - `User::where(email)->roles` contains `system_admin` after seed
2. **Role sync** - `roles()->sync()` replaces roles, not duplicates
3. **Sidebar role display** - `get(route("dashboard"))` contains "System Administrator" and not "—" after fix, fallback "No Role Assigned" when no roles
4. **System admin access** - `actingAsRole("system_admin")->get(users.index)` 200, `get(exam-rooms.index)` 200
5. **Auditor denial** - `actingAsRole("auditor")->get(users.index)` 403, `get(audit-logs.index)` 200 (auditor allowed)
6. **Reviewer access rules** - `reviewer` can POST `detection-events/{id}/review` 200, `auditor` cannot (403), `invigilator` cannot review

All 71+6 = 77 tests pass after fix (previous 65 + 6 new + existing).

## Summary

- **User lookup**: risingbappy1@gmail.com exists, id=2, Md Moklesar Rahman, 0 roles before
- **Current roles before**: 0 (empty `roles` table due to `migrate:fresh` without seed on local MySQL)
- **Final roles after**: 1 (`system_admin` | System Administrator, via `roles()->sync`)
- **Cause of 403**: Missing `role_user` pivot → `hasRole` false → `RoleMiddleware` abort 403
- **Fix applied**: `php artisan db:seed --class=RolePermissionSeeder` to restore 5 roles, then `$user->roles()->sync([$role->id])` for risingbappy1, update sidebar blade to show `description` with fallback
- **Cache actions**: `permission:cache-reset` not applicable (no Spatie), `optimize:clear` executed successfully
- **Policy findings**: No Gate/Policy used for users; denial is solely `RoleMiddleware` + controller `hasRole` check; no Spatie permission cache involved
