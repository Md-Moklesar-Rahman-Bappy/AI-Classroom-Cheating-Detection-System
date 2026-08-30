# Changelog

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
