# Dashboard — AI Classroom Cheating Detection System

## Project Name
AI Classroom Cheating Detection System — Dashboard (Laravel 11)

## Dashboard Purpose
Human-review interface for examination surveillance. Receives jobs/events/evidence from the AI Service, enforces RBAC, and provides audit-grade review workflow. The AI Service never decides misconduct; the Dashboard surfaces observable events for authorized human reviewers.

## Architecture Overview
- Laravel 11 + PHP 8.2, Blade + Bootstrap, Vite
- MySQL `ai_classroom` (15 tables via `2026_08_30_132716` + ENUM extension `2026_09_06_000001`)
- Eloquent models: `User/Role/Permission`, `ExamRoom/Session`, `CameraSource`, `VideoAsset`, `AnalysisJob`, `DetectionEvent`, `EventEvidence`, `ReviewDecision`, `AuditLog`
- Services: `AiServiceClient` (Guzzle) → FastAPI; Jobs: `ProcessAnalysisJob/SyncAnalysisJob`
- Routes in `routes/web.php` (auth + role middleware), Policies `VideoAssetPolicy/AnalysisJobPolicy`, `RoleMiddleware`
- Frontend: `resources/views/*` (dashboard, video-assets, analysis-jobs, detection-events, evidence, reports, metrics, audit-logs), `public/` via Vite 7.3.6

## Features
- Exam room/session/camera CRUD with soft-delete + restore
- Recorded video upload → AI Service analysis; annotated output + event timeline
- Live surveillance sessions (start/stop/health/events/preview) via AI Service
- Event review: filter by `event_type`/`track_id`/`review_status`, paginated, with evidence thumbnails
- Evidence: index/show/download, bulk delete/restore, filtered by `event_type`/`file_type`
- Review decisions: `confirmed_suspicious`/`dismissed_normal`/`needs_further_review` with audit log
- Model versions, processing metrics, audit logs, reports (show/download), metrics throughput
- Responsive sidebar offcanvas (<992px), design tokens `sidebar #0F172A / primary #2563EB`, Inter+JetBrains Mono, Chart.js trend

## Roles
| Role | Access |
|---|---|
| system_admin | Full access; user/role management, all evidence, audit logs, settings |
| exam_admin | Sessions, cameras, video assets, analysis jobs, evidence, reports |
| invigilator | Monitor exams, view events/evidence, live sessions |
| reviewer | Review suspicious events, evidence, review decisions |
| auditor | Read-only: events, evidence, audit logs, reports |

Seeded via `RolePermissionSeeder` (5 roles, 10 permissions, demo users `admin@example.com` etc., `Password123!`). Sidebar role badge shows `roles->first()->description` fallback.

## Setup Instructions
```bash
cd dashboard
composer install
cp .env.example .env   # set DB_CONNECTION=mysql DB_DATABASE=ai_classroom AI_SERVICE_URL=http://127.0.0.1:8001
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder   # local/testing only
npm install && npm run build
php artisan serve --port=8000
php artisan queue:work  # for ProcessAnalysisJob
```
Tests: `php artisan test` (Pest, 171 tests). Queue failures retry via `AnalysisJobController@retry`.

## Database Requirements
- MySQL 10.4+ `ai_classroom`; default `config/database.php` is `mysql` (not sqlite); `phpunit.xml` uses `sqlite :memory:` for testing only
- Migrations additive with `hasColumn` guards; `migrate:fresh --env=testing` only; never `migrate:fresh` on MySQL
- SQLite file `database/database.sqlite` kept for CI only, gitignored
- See `docs/DATABASE_DESIGN.md` and `docs/DATABASE_IMPLEMENTATION.md` (appendix includes persistence audit)

## Queue Requirements
- `QUEUE_CONNECTION=database` (or redis) for `ProcessAnalysisJob`/`SyncAnalysisJob`; run `queue:work` and `migrate` for jobs table; failed jobs table inspected via `reports`

## AI Service Integration
- Base URL `AI_SERVICE_URL` (default `http://127.0.0.1:8001`), Guzzle client `AiServiceClient` with timeout + `health()` check (`GET /api/v1/health`)
- Endpoints: `POST /api/v1/jobs/recorded`, `GET /api/v1/jobs/{id}`, `POST /api/v1/jobs/{id}/cancel|retry`, `GET /api/v1/jobs/{id}/events|metrics`, `POST /api/v1/live/start|stop`, `GET /api/v1/live/{id}/health|events|preview`
- File transfer: `VideoAsset` stored `storage/app/video_assets` then streamed to AI Service; fallback `debug/analyze-local` gated by `is_development()`
- Health surfaced on Dashboard KPIs (`/dashboard` + `/health/ai`); direct `DB::listen()` validation logged in `VIDEO_ASSET_RUNTIME_QUERY_TRACE.md` (archived)

## Event Taxonomy Summary (11 events, V2)
Detection: D1 Person (green), D2 Mobile Phone blue, D3 Multiple Persons yellow. Behavior: B1 Looking Left, B2 Looking Right, B3 Looking Back, B5 Excessive Head Movement (orange), B4 Possible Seat Departure red. System: S1 Normal green, S2 Insufficient Evidence gray, S3 Tracking Lost gray. Every page shows responsible-AI notice. Details in `docs/EVENT_TAXONOMY_V2.md` + `AUDIT_REPORT.md`.

## Evidence Workflow
1. AI Service `EvidenceManager.save_snapshot(frame, job_id, event_id, frame_index, timestamp)` annotates via `EvidenceAnnotator` (triggering track colored + Track #/Code/Frame label; others gray 1px) and persists `event_evidence` (`frame_number`, `captured_at_seconds`, `bbox`, `checksum_sha256`)
2. Dashboard `EvidenceController` lists (12/page), shows full-resolution annotated jpg, downloads, bulk actions with `VideoAssetPolicy`/`AnalysisJobPolicy` + `role` middleware
3. ReviewDecision appends `detection_events.review_status` and audits via `AuditHelper`
4. See `docs/EVIDENCE_ANNOTATION_SYSTEM.md` + `EVIDENCE_DOWNLOAD_GUIDE.md`

## Camera Management
- `CameraSource` model with encrypted `credentials_encrypted` (hidden, `Crypt::encryptString`), source types `rtsp`/`webcam`, health polling via AI Service `LiveSession`
- Routes `live/*` gated `role:system_admin,exam_admin,invigilator` for start/stop, all roles for view/health/events
- Compatibility notes in `docs/CAMERA_SETUP.md`, `EZVIZ_CP1_LITE_COMPATIBILITY.md`, `LIVE_SURVEILLANCE_MODE.md`

## Security Notes
- Auth via Laravel Breeze (login/register/forgot/verify/confirm), password visibility toggles, strength indicator client-only, delete guard blocks last `system_admin` (`ProfileController`)
- RBAC via `RoleMiddleware` (`role:system_admin,...`) on every resource route; unauthorized blocked with 403 and audit log
- Evidence storage protected by policies + `authorizeAccess`; API validation via FormRequests; no camera credentials in query strings
- Audit logging via `AuditHelper::log` on video_uploaded/updated/deleted, user_created/role_sync, review decisions; retained and filterable in `audit-logs`
- `.env` never committed; `THIRD_PARTY_NOTICES.md` documents YOLO AGPL-3.0; responsible disclosure to `md.moklasarrahmanbappy@gmail.com`

## Related Docs
- `docs/ARCHITECTURE.md`, `docs/API_CONTRACT.md`, `docs/AUTHORIZATION_MATRIX.md`, `docs/SECURITY_AUDIT.md`, `docs/THREAT_MODEL.md`, `docs/AUDIT_REPORT.md`, `docs/TECHNICAL_SUPPLEMENT.md`
