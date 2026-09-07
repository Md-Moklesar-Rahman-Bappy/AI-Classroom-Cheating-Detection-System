# Project Structure Report — Phase 1
Date: 2026-09-07 | Root: C:\xampp\htdocs\ai_classroom_cheat_detection

## Root Map
- `dashboard/` Laravel 11 (app/Http/Controllers 16, Models 14, Services/Jobs/Policies/Middleware, routes web/auth/console, resources/views blade, database/migrations 11, seeders 1, tests Feature 13 + Unit)
- `ai-service/app/` FastAPI (api/jobs|live|health, detection/yolo_detector, tracking/centroid_tracker, orientation/geometric, behaviors/rules|engine|config, events/taxonomy, evidence/manager|annotator, jobs/service, live/session, rendering, inputs/recorded|live, schemas, config/settings, main.py)
- `docs/` 79 active md + `docs/archive/` 33 + `docs/audit/` 4
- `research/` 8 md (ethics/dataset)
- `scripts/` benchmark.py, generate_complete_documentation.py
- `storage/`, `outputs/`, `evidence/`, `ai-service/storage|outputs` — gitignored artifacts (105+138 mp4)
- `.github/` (if present) — CI not found, local .gitignore covers .env, vendor, node_modules, *.pt, *.mp4
- `yolo11n.pt` (5.6 MB) + `ai-service/yolo11n.pt` (dup, nested duplicate removed)
- `ai_classroom.sql` (33 KB dump), `AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx` (75 KB thesis)

## Dashboard Tree Verified
Controllers all routed (web.php 35 routes); Models with fillable/relations/hidden; AiServiceClient Guzzle; Jobs ProcessAnalysisJob/SyncAnalysisJob; Policies; RoleMiddleware; Helpers AuditHelper.

## AI-Service Tree Verified
main.py includes 3 routers; all 79 .py transitively imported via jobs/service → engine chain; 17 tests in ai-service/tests.

## Docs/Research Counts
docs 79 active (thesis 34, event 9, deployment 8, etc.), archive 33 (25 net archived +2 new consolidated AUDIT_REPORT/TECHNICAL_SUPPLEMENT), research 8, root 20 md, service readmes 2.

## Vendor Independence
vendor/ 80 MB, node_modules 60 MB regeneratable; .mypy/.pytest/.ruff caches removed (34 MB saved). No vendor code audited.

## Verdict
Structure intact, no orphan top-level folders, all expected thesis folders present.
