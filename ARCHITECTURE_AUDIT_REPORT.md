# Architecture Audit Report — Phase 2
Date: 2026-09-07

## Verdicts
| Layer | Status | Evidence |
|---|---|---|
| Dashboard (Laravel) | Implemented | web.php 35 routes, 16 controllers, Blade shell with tokens, Vite 7.3.6 59 modules |
| API (Laravel→FastAPI) | Implemented | AiServiceClient 8 endpoints (recorded/live/health), Guzzle timeout, health surfaced on /dashboard KPI |
| AI Service (FastAPI) | Implemented | main.py 3 routers + root + debug, Uvicorn, Pydantic |
| Tracking | Implemented | SimpleCentroidTracker(max_distance 90, max_missing 15) post-accuracy tuning, process_every_n 3 |
| Evidence | Implemented | EvidenceManager.save_snapshot at packet.frame_index, EvidenceAnnotator single-highlight, EventEvidence persisted |
| Events (11) | Implemented | taxonomy.py 11 codes D1-D3/B1-B5/S1-S3, rules.py thresholds window 15 etc., engine.py mark_missing |
| Database (MySQL) | Implemented | 15 tables, 11 migrations, foreign keys cascade, ENUM v2 11 values, persistent MySQL verified |
| RBAC | Implemented | 5 roles, 11 permissions, RoleMiddleware on 10 routes, Policies |
| Camera Management | Implemented | CameraSource encrypted credentials, LiveSession start/stop/health/events/preview, 5 routes gated |

No stubs, no broken layers. All partially-implemented previously (e.g., live mode) now verified via LiveModeTest + recorded pipeline.

## Consistency
Config flow: settings.py BaseSettings ↔ behaviors/config.py ↔ service.py wiring; DB_CONNECTION mysql default; ALLOWED_EVENT_TYPES consistent across migration ENUM and taxonomy.py EVENT_CODE_MAP.
