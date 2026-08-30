# Changelog

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
