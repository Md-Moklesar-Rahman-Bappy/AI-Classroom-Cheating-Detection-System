# Database Implementation

## Migration
- `2026_08_30_132716_create_phase5_foundation_tables` creates 15 tables + pivots: roles, permissions, permission_role, role_user, exam_rooms, exam_sessions, model_versions, camera_sources, video_assets, analysis_jobs, detection_events, event_evidence, review_decisions, processing_metrics, audit_logs, retention_actions
- Foreign keys with cascade/nullOnDelete as per DATABASE_DESIGN.md, indexes on name/email/status/type, unique on checksum, stored_filename

## Models
- All in `app/Models` with fillable, casts, hidden (credentials_encrypted), relationships (belongsTo/hasMany/belongsToMany)
- User has roles() + hasRole/hasAnyRole/hasPermission
- ExamSession has room/creator/videoAssets/analysisJobs
- DetectionEvent has job/evidences/session

## Factories
- Default UserFactory (Breeze) with HasFactory, Notifiable

## Seeders
- `RolePermissionSeeder` creates 5 roles (system_admin, exam_admin, invigilator, reviewer, auditor) + 10 permissions + 5 demo users (admin@example.com etc, Password123!) only if app()->environment in [local,testing], warns otherwise

## Transactions & Indexes
- Migrations use foreignId constrained, indexes on exam_room_id/status, exam_session_id/type, etc.

## Soft Deletes
- Not used (not justified per spec)

## Enums
- Validated strings via enum in migration + request validation `in:...`

## Credential Fields
- `camera_sources.credentials_encrypted` text, encrypted via Crypt::encryptString, hidden, never serialized, has_credentials accessor

## Serialization
- Hidden password/remember_token, credentials_encrypted, file_path via controller only (signed route later)

## Verification
- `php artisan migrate:fresh --seed` in testing with sqlite :memory: passes (RefreshDatabase)
- `php artisan migrate --force` on MySQL 10.4.32 succeeds
  
---  
## Appendix: Persistence Audit (merged from /DATABASE_PERSISTENCE_AUDIT.md 2026-09-06)  
Archived source: docs\archive\DATABASE_PERSISTENCE_AUDIT.md  
  
# Database Persistence Audit — 2026-09-06

## Summary
MySQL persistent storage verified. No SQLite dependency in production. In-memory AI-service store is intentional architecture.

## Findings

| Area | Before | After | Status |
|------|--------|-------|--------|
| DB_CONNECTION | `.env.example` defaulted to `sqlite` | Changed to `mysql` with `ai_classroom` | ✅ Fixed |
| config/database.php default | `sqlite` | Changed to `mysql` | ✅ Fixed |
| phpunit.xml | `sqlite :memory:` for tests only (correct) | Unchanged — isolated to testing | ✅ OK |
| Migrations destructive | All `up()` additive; `down()` drop only on rollback | Added `hasColumn` guards | ✅ Safe |
| `migrate:fresh` risk | Docs instructed `migrate:fresh` without env guard | Documented in policy | ⚠️ Operator discipline |
| Seeders | `sync()` overwrites perm mappings; legacy email | Fixed: `examadmin@example.com` + `password` | ✅ Idempotent |
| AI-service repos | `InMemoryJobRepository` / `InMemoryEventRepository` | Intentional — docs acknowledge | ℹ️ By design |
| SQLite file | `dashboard/database/database.sqlite` exists (253KB) | Kept for CI only — gitignored | OK |

## Data-Loss Risks Ranked

| Risk | Level | Mitigation |
|------|-------|------------|
| Operator runs `migrate:fresh` on MySQL | **Critical** | Use `migrate --force` only; `fresh` requires `--env=testing` |
| AI-service restart clears jobs/events | High | Expected; future: add DB persistence for jobs |
| SQLite fallback if DB_CONNECTION missing | Medium | Fixed default to `mysql`; `.env` is now explicit |
| `down()` migration truncates ENUM | Low | `2026_09_06_000001` down would drop D3/B5/S1-3 |

## Verification

```bash
# Production uses MySQL
cat dashboard/.env | grep DB_CONNECTION  # mysql
cat dashboard/config/database.php | grep "'default'"  # mysql

# Tests isolated
cat dashboard/phpunit.xml  # sqlite :memory: only under APP_ENV=testing

# AI-service is in-memory (expected)
grep -r InMemory ai-service/app  # job + event repos
```

## Required State ✅
- MySQL only for persistent data
- No `:memory:` leak outside tests
- Safe migrations with `softDeletes` and nullable adds
- No accidental data loss under `migrate --force`
