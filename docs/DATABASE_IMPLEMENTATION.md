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
