# Dashboard Architecture

## Stack
- Laravel 12.68.0 (PHP 8.2.12), Blade, Bootstrap 5.3 (CDN), Vite 7.3, MySQL 10.4.32 MariaDB, Chart.js 4, SweetAlert2 (reserved)
- Breeze 2.4.2 Blade with Pest, Pint, Vite

## Structure
- `app/Models` 15 entities (Role, Permission, ExamRoom, ExamSession, CameraSource, VideoAsset, AnalysisJob, ModelVersion, DetectionEvent, EventEvidence, ReviewDecision, ProcessingMetric, AuditLog, RetentionAction, User)
- `app/Http/Controllers` 12 controllers (Dashboard, ExamRoom, ExamSession, CameraSource, VideoAsset, AnalysisJob, DetectionEvent, Evidence, ReviewDecision, ModelVersion, AuditLog, User) + Breeze Auth
- `app/Http/Middleware/RoleMiddleware` alias `role`
- `app/Helpers/AuditHelper` logs to audit_logs
- `routes/web.php` auth+verified group, role middleware for users/audit/evidence
- `resources/views/layouts/bootstrap.blade.php` + Breeze `layouts/app`/`guest`
- `database/migrations` 0001 users/cache/jobs + 2026_08_30_132716 Phase5 foundation
- `database/seeders/RolePermissionSeeder` local/testing only

## Auth Flow
- Breeze login/logout/password-reset/email-verification, rate limiting (throttle:5), session regeneration after login, invalidation after logout, strong password (min 8 letters numbers symbols), no public registration (register route exists but can be disabled via middleware)

## Authorization
- `RoleMiddleware` checks `hasRole`, 403 if insufficient
- `User::hasRole/hasAnyRole/hasPermission`
- Evidence: controller checks `hasAnyRole` + no `..` in path, storage outside public, fake disk for tests
- Audit: `role:system_admin,auditor,exam_admin` for logs

## Evidence Protection
- Files under `storage/app/video_assets` and `storage/app/private` (via Storage::disk local), not in `public/`
- Access via `EvidenceController@show` with auth, role, safe file resolution, no user-supplied path, audit logged

## AI Notice
- Layout ai-notice + Help page, event show separates machine observation / supporting evidence / human decision / audit history, never displays “cheater”

## Vite
- `vite.config.js` default, `resources/css/app.css` + `js/app.js` (Tailwind), Bootstrap via CDN for dashboard, build via `npm run build` succeeds

## Testing
- Pest, RefreshDatabase, sqlite :memory:, RolePermissionSeeder beforeEach
