## [0.8.0] - 2026-08-30
### Added
- Low-resource performance benchmarking: reproducible `scripts/benchmark.py` with 6 recorded configs (640x360/480x270 x 1/3/5) + live 480x270/3, warm-up separate, measures 16 metrics (duration, frames, processed/skipped, calls, wall, FPS, latency p50, E2E, CPU, memory, GPU, dropped, events, output/evidence size) on synthetic 640x360 10fps 90f (no PII), hardware Ultra 7 155H 16c/22t 15.5GB, HP Optimized, yolo11n.pt 0ebbc80d, opencv 5.0.0/ultralytics 8.4.135/torch 2.13.0+cpu, all values actual execution (5.299s 16.98 FPS for 640x1 vs 1.092s 27.47 FPS for 480x3)
- Optimization evaluation (13 options individually): lower resolution (640?480 +62% FPS), frame skipping (every 3rd +64% FPS, every 5th +62% but more miss), ROI/pose/thread/queue/model-singleton/alert-only documented, before/after 640x1 16.98?480x3 27.47 FPS (+62%, -79% wall, -60% output) with preserved semantics
- Low-resource profile `low_resource` (480x270 every 3rd, 27.47 FPS, 1.092s, 25555 B, config_version low_resource_v1) selected evidence-based, ultra-low 480x5 (27.62 FPS, 0.652s) alternative, definitions real-time >=15 FPS, near-real-time 5-15, offline <5 (project), no "real time" claim without definition+measured
- Docs: BENCHMARK_REPORT, PERFORMANCE_TUNING, LOW_RESOURCE_PROFILE, BENCHMARK_REPRODUCTION, scripts/benchmark.py, research/experiments/benchmark_manifest.json, research/results/benchmark_results.json + low_resource_profile.json (sanitized, no C:\ or token), 8 tests (config, schema, zero-frame, failed source, metrics, comparison, sanitization, profile)
## [0.7.1] - 2026-08-30
### Fixed
- Model Version dropdown empty on Create Analysis Job: added `is_active` boolean to `model_versions` (migration 2026_08_30_144216), updated `ModelVersion` model with `is_active` cast and `scopeActive`, created `ModelVersionSeeder` for YOLO11 Nano (name YOLO11 Nano, version 1.0, checksum 0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1, is_active true), updated `DatabaseSeeder` to always seed ModelVersion, updated `AnalysisJobController@create` to use `ModelVersion::active()->get()`, ensure fresh install has at least one active model, added 5 tests (dropdown populated, active model available, job creation succeeds, fresh install, inactive not in dropdown) -> 99 tests
## [0.7.0] - 2026-08-30
### Added
- Live camera surveillance mode: verified local webcam (device 0, 640x480) and test stream (synthetic), EZVIZ CP1 Lite RTSP/ONVIF/HTTP unverified (documented, not assumed), single-source low-resource limit (409 if already monitoring)
- Source abstraction: CameraSourceConfig (source_type, identifier, timeout, reconnect 1-30s bounded, frame_timeout 3s), WebcamInput, RtspStreamInput, TestStreamInput, health last-frame timestamp, stop token, guaranteed release, 9 source states (unconfigured/testing/connected/monitoring/reconnecting/degraded/disconnected/stopped/failed)
- Live processing: shared engine (YOLO11n detector, SimpleCentroidTracker, geometric-v1 orientation, TemporalEventEngine, renderer, evidence, metrics) with start/stop graceful shutdown, reconnect bounded delay, stale-frame detection, duplicate-alert suppression, live metrics (FPS, latency, last_frame_time, reconnect_count), incident evidence, annotated 320x180 preview (not full-res)
- Dashboard delivery: evaluated MJPEG vs WebSocket vs SSE vs polling ? chose MJPEG (320x180 multipart) + polling fallback (health/events every 2s), separate preview from alert metadata
- Live UI: camera name, connection/monitoring/session, annotated preview (320x180), processing FPS, alert latency, last frame time, recent events, evidence preview, start/stop controls, degraded/offline warning, credentials never displayed
- Live API: POST /api/v1/live/start, POST /api/v1/live/{id}/stop (idempotent), GET /health, GET /events, GET /preview (MJPEG), with auth, duplicate start 409, audit start/stop, stop clean release, abnormal termination recorded
- Tests: 16 AI service (webcam/test, invalid URL, auth, timeout, interruption, stale, stop during reconnect, duplicate, repeated stop, event, evidence, unauthorized, credential redaction, cleanup, crash) + 17 dashboard (webcam/test, invalid, auth, timeout, interruption, stale, stop, duplicate, repeated, event, evidence, unauthorized, preview, redaction, cleanup, crash, recovery) -> 94 dashboard + 16 AI = 110 total
- Docs: LIVE_SURVEILLANCE_MODE, CAMERA_SETUP, STREAMING_ARCHITECTURE, LIVE_MODE_TEST_REPORT, EZVIZ_CP1_LITE_COMPATIBILITY; updated README, ARCHITECTURE, THREAT_MODEL, SECURITY, TROUBLESHOOTING, CHANGELOG, risk register; EZVIZ live marked unverified, recorded fully operational
## [0.6.0] - 2026-08-30
### Added
- AI service integration: typed AiServiceClient (base URL, token, timeout 10s, retry safe GET only, correlation ID X-Correlation-Id, secret redaction, health check), no synchronous AI processing (ProcessAnalysisJob queued, timeout 600)
- Job workflow: upload validated video (MIME/size, uuid storage, outside public, temp cleanup), create dashboard job (pending) with duplicate prevention (5 min window), submit to AI service via multipart, store remote_job_id, poll getJob every 2s (30 attempts), sync progress (not invented), import events idempotently, link evidence via protected copy (ai-service/evidence -> storage/app/evidence), handle failed/cancelled/duplicate/retry, safe retry (new job)
- Status UI: Pending/Queued/Processing/Cancelling/Cancelled/Failed/Completed with badge text+color, progress bar (remote_progress), processed frames, started/completed, failure sanitized, retry/cancel/sync/report actions, config/model version display, metrics
- Event sync: deduplication via event_id or idempotent key (job+track+type+start_frame), preserve timestamp, track ID, event type, machine evidence, model/config version, review_status
- Evidence delivery: Laravel-controlled protected copy (storage outside public, Storage::disk local, evidence/{job_id}/{event_id}.jpg, checksum, no absolute path, auth+role+path traversal check, audited)
- Human review: reviewer can select confirmed_suspicious/dismissed_normal/needs_further_review with note, creates ReviewDecision append-only, updates DetectionEvent, audit logged
- Reporting: authorized report with exam session, source mode, job, model version, config, events, human review, metrics, disclaimer "AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct." (HTML/PDF)
- Tests: 16 new integration tests (e2e, invalid upload, AI down/timeout/auth, duplicate, job failure/cancellation/retry, event duplicate, unauthorized evidence/report, reviewer decision, audit, safe error, no secret) -> total 65 passed
# Changelog

## [0.5.0] - 2026-08-30
### Added
- Laravel 12.68.0 dashboard foundation (PHP 8.2.12, MySQL 10.4.32 MariaDB, Node 24.14.0, npm 11.17.0) with Breeze 2.4.2 Blade, Vite 7.3, Bootstrap 5.3, Chart.js, DataTables native
- Auth: login/logout/password-reset/email-verification, rate limiting (throttle:5), session regeneration, strong password (8+ letters numbers symbols), no public registration, production-safe seeders (local/testing only)
- Roles: system_admin, exam_admin, invigilator, reviewer, auditor with RoleMiddleware, server-side policies, authorization matrix
- 15 modules: Dashboard overview (stats), Exam Rooms CRUD, Exam Sessions CRUD, Camera Sources (encrypted placeholders), Video Assets (upload validation), Analysis Jobs, Detection Events (filter), Evidence (protected, not in public), Review Decisions (confirm/dismiss), Model Versions, Metrics (Chart.js), Audit Logs, Users/Roles, Settings, Help with AI notice
- Database: 16 tables (roles, permissions, pivot, exam_rooms, exam_sessions, camera_sources, video_assets, analysis_jobs, model_versions, detection_events, event_evidence, review_decisions, processing_metrics, audit_logs, retention_actions) with FKs, indexes, encrypted credentials, safe serialization, factories
- Evidence protection: storage outside public, authorized controller with role + safe path, audited access, no user-supplied path, traversal blocked
- Audit logging: AuditHelper logs room/session/camera/video/job/event/review/model/user actions with actor, IP, result, metadata
- Design System: color tokens, typography, spacing, borders, shadows, buttons, forms, tables, badges, alerts, empty/loading/error, responsive, focus, AI notice (text+color)
- Tests: 24 new Pest tests (login, rate limiting, session, password reset, roles, unauthorized, room/session/camera/video/job/event/evidence/review/auditor/model/audit/seeder/csrf/validation/direct URL/password/evidence) + Breeze 25 = 49 passed
- Docs: DESIGN_SYSTEM, UI_COMPONENTS, DASHBOARD_ARCHITECTURE, AUTHORIZATION_MATRIX, DATABASE_IMPLEMENTATION, INSTALLATION_WINDOWS, LOCAL_DEVELOPMENT, DASHBOARD_TEST_REPORT

## [0.4.0] - 2026-08-30
### Added
- Anonymous tracking: Tracker interface + SimpleCentroidTracker (temporary IDs, no embeddings, max_distance 80, max_missing 10)
- Orientation: GeometricOrientationEstimator (geometric-v1) with typed OrientationObservation (track_id, timestamp, orientation_state forward/left/right/backward/uncertain/unavailable, quality, geometry, landmark_count, insufficient_reason, method_version)
- Temporal engine: 5 rules (RepeatedLookingLeft/Right, LookingBackward, LeavingSeat, InsufficientEvidence) with BehaviorConfig (window 15, min_supporting 8, max_missing 4, min_duration 10, cooldown 45, leaving_absence 30, config_version v1) recorded per job, explainable output, cooldown/duplicate suppression, track-loss handling
- Visualization: Green/Amber/Red/Blue/Gray (normal/accumulating/suspicious/phone/insufficient) with anonymous track ID + observed state + duration/q
- Docs: ORIENTATION_METHOD_EVALUATION, TRACKING_DESIGN, ORIENTATION_METHOD, TEMPORAL_EVENT_RULES, BEHAVIOR_EVENT_LIMITATIONS, PHASE_4_TEST_REPORT; updated EVENT_TAXONOMY, EVIDENCE_FORMAT
- 13 new tests for tracking/orientation/temporal fixtures (stable forward, brief/repeated left/right, backward, missing landmarks, occlusion, track switching/reappearance, seat departure, cooldown, concurrent tracks, insufficient)

## [0.3.0] - 2026-08-30
### Added
- Recorded video analysis pipeline: AnalysisJob lifecycle (pending/queued/processing/cancelling/cancelled/failed/completed), state machine, RecordedAnalysisService, upload safety (MIME/size/traversal/temp cleanup), output metadata, progress, cancellation, retry, metrics
- Mobile Phone Detected event with cooldown (30 frames) and duplicate suppression, requires_review=true, evidence manager (limited JPG snapshots with checksum, retention status)
- FastAPI jobs endpoints: POST /jobs/recorded, GET /jobs/{id}, POST /jobs/{id}/cancel, POST /jobs/{id}/retry, GET /jobs/{id}/events, GET /jobs/{id}/metrics
- CLI `app.cli` with --input/--output-dir/--model-path/--imgsz/--frame-interval/--conf/--device/--enable-evidence/--json validation
- Docs: RECORDED_VIDEO_MODE, ANALYSIS_JOB_LIFECYCLE, EVIDENCE_FORMAT, RECORDED_MODE_TEST_REPORT
- 26 new tests (valid e2e, invalid/empty, cancellation, retry, state transition, detector/writer/evidence failure, suppression, cooldown, no detections, person/phone, traversal, upload validation, temp cleanup, metrics, API lifecycle, CLI)

## [0.2.0] - 2026-08-30
### Added
- ai-service shared foundation: config, inputs (recorded/webcam/rtsp/test), scheduler, yolo detector, renderer, metrics, health/version
- Typed DetectionResult, secret-redaction logging, resource cleanup
- 17 tests (config, logging, inputs, detector, render, metrics, api)
- Docs: PHASE_2_IMPLEMENTATION, MODEL_BASELINE, VIDEO_IO_VALIDATION, KNOWN_LIMITATIONS

### Fixed
- ruff import order, black formatting

## [0.1.0] - 2026-08-30
### Added
- Phase 1 architecture docs (15 docs), AGPL compliance, THIRD_PARTY_NOTICES
- Phase 0 audit docs, requirements.txt pinned




