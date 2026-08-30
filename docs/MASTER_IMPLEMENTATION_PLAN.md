# Master Implementation Plan (Phases 2-10)

## Phase 2: Shared AI Foundation
- Objective: Config, logging, input adapters, detector interface, YOLO detector, renderer, metrics, health endpoint, tests.
- Entry: Phase 1 docs complete; env verified (Python 3.14.0, ultralytics 8.4.135 AGPL-3.0, 8GB RAM, no GPU).
- Tasks: Create `ai-service/app/{config,inputs,detection,metrics,api}`, implement `RecordedVideoInput`/`WebcamInput`/`RtspStreamInput`/`TestVideoInput`, YOLO wrapper (yolo11n.pt), health endpoint, structured logging.
- Files: `ai-service/app/main.py`, `app/inputs/*.py`, `app/detection/yolo_detector.py`, `app/config/settings.py`, `tests/test_adapters.py`, `tests/test_detector.py`, `tests/test_health.py`.
- Tests: Adapter open/fail, detector mock, health 200, config load, model-load failure, resource cleanup.
- Risks: Python 3.14 incompatibility (mitigated: smoke tests), 8GB RAM pressure, AGPL compliance.
- Exit: Sample video opens, persons detected, annotated output generated, invalid input fails safely, tests pass.
- Commit: `feat(ai): add shared detection foundation`
- Non-goals: Tracking, temporal rules, Laravel, live mode.

## Phase 3: Recorded Video Mode
- Objective: Full recorded pipeline (upload validation, job queue, progress, annotated output, event/evidence storage).
- Entry: Phase 2 done; shared engine works on sample video.
- Tasks: File validation (MIME/size), job lifecycle (pending/queued/processing/completed/failed/cancelled), progress reporting, annotated writer, evidence snapshots, metrics.
- Files: `ai-service/app/api/jobs.py`, `app/evidence/manager.py`, `app/streaming/writer.py`, `tests/test_recorded_pipeline.py`.
- Tests: Valid/invalid video, job cancel/retry, progress accuracy, annotated video exists, evidence saved.
- Risks: Disk full, corrupt video, memory pressure.
- Exit: Upload -> analysis -> annotated video -> events/metrics stored -> failures visible/retryable.
- Commit: `feat(recorded): add recorded-video analysis pipeline`
- Non-goals: Tracking/behavior events (Phase 4), dashboard UI.

## Phase 4: Tracking and Behavior Events
- Objective: Anonymous tracking, phone event, head-orientation, temporal rules (B1-B4), cooldown, duplicate suppression.
- Entry: Phase 3 recorded output works; YOLO detects person/phone.
- Tasks: ByteTrack/DeepSORT integration, MediaPipe or YOLO-pose orientation, temporal engine (min consecutive observations, cooldown), evidence manager integration.
- Files: `app/tracking/tracker.py`, `app/pose/orientation.py`, `app/behaviors/rules.py`, `app/events/engine.py`, `tests/test_tracking.py`, `tests/test_temporal_rules.py`.
- Tests: Tracker ID persistence, B1-B4 thresholds, insufficient evidence, cooldown, duplicate suppression.
- Risks: Orientation not generalizing; CPU cost of pose; threshold tuning needs validation data.
- Exit: Every event traceable to rule/detector; single-frame noise not generating alerts; insufficient evidence handled.
- Commit: `feat(events): add temporal behavior rules`
- Non-goals: Advanced interaction events (roadmap), Laravel.

## Phase 5: Dashboard Foundation
- Objective: Laravel auth, RBAC, users/rooms/sessions/cameras/video_assets/jobs/events/reviews/audit, professional design.
- Entry: Phase 4 events work via API; PHP 8.2.12 verified; MySQL available.
- Tasks: Choose Laravel release compatible with PHP 8.2.12, install via Composer, Blade+Bootstrap 5, migrations for all 15 entities, policies, audit logging.
- Files: `dashboard/app/Http/Controllers`, `app/Policies`, `database/migrations/*`, `resources/views/*`, `tests/Feature/*`.
- Tests: Auth, authorization (403 on forbidden), evidence auth, review workflow, audit, rate limiting.
- Risks: PHP/Composer version mismatch; MySQL not running; AGPL not affecting Laravel (separate process).
- Exit: Every protected operation server-side authorized; evidence not accessible by unauthorized roles; no default prod credentials.
- Commit: `feat(dashboard): add foundation with RBAC`
- Non-goals: AI processing inside web request; recorded integration (Phase 6).

## Phase 6: Recorded Dashboard Integration
- Objective: Laravel <-> AI service integration for recorded mode.
- Entry: Phases 3 and 5 done.
- Tasks: Service-to-service auth (bearer token), job creation proxy, progress polling/SSE, annotated video download via signed route, event timeline, review actions, audit.
- Files: `dashboard/app/Services/AiServiceClient.php`, `app/Http/Controllers/AnalysisJobsController.php`, `tests/Feature/RecordedIntegrationTest.php`.
- Tests: Upload -> job -> progress -> events/evidence -> review -> audit; service outage handled.
- Risks: Token leakage; network failure; payload size.
- Exit: Authorized user uploads, job processes, dashboard shows progress/events/evidence, reviewer decision recorded, audit exists.
- Commit: `feat(integration): connect dashboard to AI service (recorded)`
- Non-goals: Live mode (Phase 7).

## Phase 7: Live Mode
- Objective: Camera abstraction, connectivity test, start/stop, health, reconnect, annotated preview, alert delivery.
- Entry: Phase 6 recorded integration works; camera abstraction designed.
- Tasks: Camera source CRUD with encrypted credentials, test endpoint, live start/stop, health polling, reconnect logic, MJPEG/SSE/polling preview, alert queue.
- Files: `ai-service/app/inputs/camera_source.py`, `app/streaming/live.py`, `dashboard/app/Http/Controllers/LiveSessionsController.php`, `tests/test_live.py`.
- Tests: Webcam works, stream failure visible, monitoring stops cleanly, credentials protected, live events appear.
- Risks: EZVIZ RTSP unavailable (fallback webcam), stream injection, reconnect storm.
- Exit: Local webcam or compatible stream works; failures visible; credentials protected; live events in dashboard.
- Commit: `feat(live): add camera stream health monitoring`
- Non-goals: Multi-camera, pan/tilt control, audio.

## Phase 8: Performance Optimization
- Objective: Benchmark and tune for i5-14500 + 8GB RAM, no GPU.
- Entry: Phases 3-7 functional; benchmark script exists.
- Tasks: Benchmark 640x360 vs 480x270, every frame vs every 3rd vs every 5th; collect FPS, latency, CPU, memory; choose default low-resource profile.
- Files: `scripts/benchmark.py`, `docs/BENCHMARK_REPORT.md`, `docs/PERFORMANCE_TUNING.md`, `docs/LOW_RESOURCE_PROFILE.md`, `results/benchmarks/*.json`.
- Tests: Benchmark reproducibility; no correctness regression.
- Risks: Benchmark results worse than expected; must not fabricate.
- Exit: Multiple resolutions/frame intervals benchmarked; actual results documented; default low-resource config selected from evidence.
- Commit: `perf: benchmark and tune low-resource profile`
- Non-goals: Model training, accuracy claims without measurement.

## Phase 9: Security and Privacy Review
- Objective: Audit auth, authz, uploads, secrets, APIs, sessions, evidence, exports, retention, logs, deps, debug settings.
- Entry: Phases 5-8 done; system feature-complete for MVP.
- Tasks: Manual + automated audit, dependency scan (`pip audit`, `composer audit`), fix verified issues, add regression tests.
- Files: `docs/SECURITY_AUDIT.md`, `docs/PRIVACY_REVIEW.md`, `docs/REMEDIATION_REPORT.md`, `tests/security/*`.
- Tests: All threat-model verification tests (T01-T20) pass; unauthorized evidence 403; credential not in logs.
- Risks: Finding requiring license/legal review blocks release.
- Exit: Issues fixed, regression tests added, no High residual risks.
- Commit: `fix(security): harden evidence and credential handling`
- Non-goals: New features.

## Phase 10: Final QA and Documentation
- Objective: Full test suite, lint, typecheck, asset build, API + E2E tests, recorded/live auth tests, docs finalization.
- Entry: Phase 9 remediation done.
- Tasks: Run `pytest`, `ruff`, `black --check`, `mypy`, `php artisan test`, `npm run build`, API tests, E2E (upload->review, start/stop live, stream disconnect, unauthorized), generate FINAL_QA_REPORT, RELEASE_CHECKLIST, KNOWN_LIMITATIONS, USER_ACCEPTANCE_TEST, RELEASE_NOTES, update README/CHANGELOG.
- Files: `docs/FINAL_QA_REPORT.md`, `docs/RELEASE_CHECKLIST.md`, `docs/KNOWN_LIMITATIONS.md`, `docs/USER_ACCEPTANCE_TEST.md`, `RELEASE_NOTES.md`, `README.md`, `ARCHITECTURE.md` final.
- Tests: All suites green; coverage measured not invented; no secrets in repo.
- Risks: Last-minute failures require phase rollback.
- Exit: Recorded works, live works with verified source, shared engine, bounding boxes + labels, temporal events, evidence review, unauthorized denied, performance measured, docs complete, QA truthful.
- Commit: `docs(release): finalize QA and documentation`
- Non-goals: New features beyond MVP.
