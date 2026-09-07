# Database Final Report — Phase 4
Date: 2026-09-07

## Migrations (11)
0001 users/cache/jobs, 2026_08_30_132716 phase5 15 tables + pivots, 134430 remote_job_fields, 144216 is_active, 150539/160000 softDeletes, 09_06_000001 v2 ENUM 11 values, 000002 softDeletes+v2 management, 094126 softDeletes rooms/sessions/users. All up() additive, down() drop only, hasColumn guards added per persistence audit.

## Schema Verified
Tables: roles/permissions/permission_role/role_user, exam_rooms/sessions, model_versions, camera_sources, video_assets, analysis_jobs, detection_events, event_evidence, review_decisions, processing_metrics, audit_logs. FK cascade/nullOnDelete per DATABASE_DESIGN, indexes on name/email/status/type, unique checksum/stored_filename.

## Relationships
User→roles M2M hasRole/hasAnyRole/hasPermission; ExamSession→room/creator/videoAssets/analysisJobs; DetectionEvent→job/evidences/session; EventEvidence→event/job. All Eloquent relations verified via grep belongsTo/hasMany.

## Indexes/FK/SoftDeletes/ENUM
- Unique permissions.name, roles.name, checksum
- ENUM detection_events.event_type 11 values D1-D3/B1-B5/S1-S3 after v2 migration (was 6)
- SoftDeletes on video_assets, analysis_jobs, rooms/sessions/users post 09_06
- In-memory AI repos intentional (job/event) not MySQL — documented

## Persistence
MySQL 10.4.32 ai_classroom `DB_CONNECTION=mysql` (default changed from sqlite), sqlite :memory: isolated to phpunit.xml APP_ENV=testing, database.sqlite kept for CI gitignored. No data loss: `migrate --force` additive, `migrate:fresh --env=testing` only, operator discipline documented, SQLite fallback fixed.

## Integrity
RolePermissionSeeder 5 roles +10 permissions +5 demo users idempotent syncWithoutDetaching; evidence bbox/frame_number/timestamp persisted via EvidenceManager; event ENUM consistent with taxonomy.py EVENT_CODE_MAP 11 entries.

## Verdict
Persistent MySQL verified, no data-loss risk beyond operator `migrate:fresh` (discipline), role/evidence/event integrity intact.
