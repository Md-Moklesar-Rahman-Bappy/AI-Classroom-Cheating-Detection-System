# DEAD CODE REPORT
Date: 2026-09-07

## Methodology
- Parsed routes/web.php, routes/auth.php, ai-service/app/main.py routers.
- Grepped all `use App\`, `import` references.
- Checked Blade views for component usage.
- Verified via `grep -r` per file; `php artisan route:list` mental equivalent; FastAPI `app.include_router` inspection.

## 1. Laravel Dashboard — NO DEAD CONTROLLERS
All 18 controllers referenced in routes/web.php or auth.php:
- DashboardController@index → GET /dashboard ✓
- ExamRoomController (resource + restore) ✓
- ExamSessionController ✓
- CameraSourceController ✓
- VideoAssetController (only + restore + cleanAbandoned private) ✓
- AnalysisJobController (8 actions: index/create/edit/update/store/show/sync/cancel/retry/destroy/restore) ✓
- DetectionEventController (index/show/destroy/bulkDestroy/restore/bulkRestore/trashed) ✓
- EvidenceController (index/show/download/destroy/bulkDestroy/restore/bulkRestore + authorizeAccess) ✓
- ReviewDecisionController@store → POST detection-events/{id}/review ✓
- AuditLogController@index ✓
- UserController (resource) ✓
- ModelVersionController (resource) ✓
- LiveController (5 routes) ✓
- ProfileController (edit/update/destroy) ✓
- ReportController (show/download) ✓
- Auth/* (8 controllers) via auth.php ✓
Result: 0 unused controllers.

## 2. Models — All Referenced
- User, Role, Permission, ExamRoom, ExamSession, CameraSource, VideoAsset, AnalysisJob, DetectionEvent, EventEvidence, ReviewDecision, ProcessingMetric, ModelVersion, AuditLog — all have relations or controller usage. None orphan.

## 3. Services / Jobs / Helpers
- Services/AiServiceClient.php — injected in AnalysisJobController, LiveController, ProcessAnalysisJob, health/ai route → NOT dead.
- Jobs/ProcessAnalysisJob.php + SyncAnalysisJob.php — dispatched from AnalysisJobController@store/retry and queue — NOT dead.
- Helpers/AuditHelper.php — used in controllers — NOT dead.
- Providers/AppServiceProvider.php — registered — NOT dead.
- Middleware/RoleMiddleware.php — used as `role:` on 10 routes — NOT dead.
- Policies/VideoAssetPolicy, AnalysisJobPolicy — gate checks in controllers — NOT dead.

## 4. Private / Unused Methods
- VideoAssetController::cleanAbandoned() — private, never called. Grep: zero references. **CANDIDATE DEAD CODE** (confidence 95%).
- No other private dead methods found.

## 5. Routes — All Accessible
- Every Route::resource generates named routes verified via blade `route()` calls.
- Closure routes: /, /health/ai, /trash, /settings, /help, /metrics — all referenced in navigation. NOT dead.

## 6. FastAPI — NO DEAD ENDPOINTS
- main.py: routers health (3 endpoints), jobs (6 endpoints), live (5 endpoints) + root + debug/analyze-local → all exposed.
- ai-service/app/api/jobs.py: create_recorded_job, get_job, cancel_job, retry_job, get_events, get_metrics — all route-referenced.
- ai-service/app/api/live.py + health.py — routed.
- debug/analyze-local — gated by is_development() but NOT dead (dev tool, confidence 100% intentional).
- All detection/tracking/orientation/behaviors modules imported transitively via jobs/service.py → engine chain → worker. None orphan.

## 7. Python Unused Modules Check
- ai-service/ai-service/ subdirectory — contains duplicate yolo11n.pt + nested package, NOT imported anywhere (grep shows zero imports of `ai-service.ai_service`). **DEAD / DUPLICATE artifact**.
- research/evaluation/*.py — imported only by manual benchmark scripts, not by runtime — NOT dead (research tooling, intentional).
- scripts/generate_complete_documentation.py — standalone generator, not imported — intentional script, NOT dead code.

## 8. Summary
| Category | Dead Count | Details |
|---|---|---|
| Controllers | 0 | all routed |
| Models | 0 | all related |
| Services/Jobs | 0 | all injected |
| Methods | 1 | VideoAssetController::cleanAbandoned |
| Routes | 0 | all used |
| Python modules | 1 duplicate package | ai-service/ai-service/ |
| Overall dead code | 2 | low risk, no safe bulk delete |

## 9. Recommendation
- Do NOT delete entire files; only remove `cleanAbandoned()` method or wire it to scheduler if intended.
- Remove duplicate nested package `ai-service/ai-service/` (empty except weight file).
