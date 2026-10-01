# Recorded Mode Test Report

## Environment (2026-08-30)
- Python 3.14.3 (tags/v3.14.3), pip 26.2.1
- `cv2` 5.0.0, `ultralytics` 8.4.135 AGPL-3.0, `torch` 2.13.0+cpu, `fastapi` 0.141.1, `pydantic` 2.13.4, `mediapipe` 1.0.1, `psutil` 7.2.2, `numpy` 2.4.6
- CPU Intel(R) Core(TM) Ultra 7 155H (16 cores, 22 logical), RAM 16GB (available ~4GB), GPU NVIDIA present but not used (CPU inference)
- Warnings: Pydantic class-based Config deprecated, FastAPI on_event deprecated (non-blocking)

## Tests Run
`python -m pytest ai-service/tests -q` -> **43 passed**, 5 warnings

Breakdown:
- Phase 2 (17): config defaults/invalid, secret redaction, valid/invalid video, capture release, frame skipping, resize, detector load/failure/mapping, renderer, writer, metrics, health/version, debug disabled
- Phase 3 (26):
  - `test_recorded_pipeline` (17): valid e2e (6f -> completed, annotated exists, metrics), invalid file (FileNotFound), empty file (ValueError), invalid state transition, cancellation (cancel_requested->cancelled), retry (failed->new queued), detector failure counts, writer failure (RuntimeError->failed), evidence failure (save returns None), duplicate suppression+cooldown (5 phones with cooldown 3 -> 2 events at 0,3), no detections (0 events, 0 evidence), person-only (0 phone events, output readable), phone detections (1 event "Mobile Phone Detected" requires_review + 1 JPG), path traversal, upload validation (unsupported .txt), temporary cleanup (no leaked tmp .mp4), metrics correctness (9f every3 -> 3 processed/6 skipped/3 invocations, fps 10, duration>0)
  - `test_jobs_api` (6): lifecycle (POST recorded -> GET job/events/metrics), invalid file 422, empty file 422, cancel/retry (completed->409 correct), not found 404, path traversal 422
  - `test_cli` (3): valid --json completes, invalid args SystemExit, --disable-evidence completes

## Coverage of Required Categories (19)
- Valid end-to-end: test_valid_e2e, test_jobs_lifecycle, test_cli_valid
- Invalid file: test_invalid_file, test_jobs_invalid_file
- Empty file: test_empty_file, test_jobs_empty_file
- Cancellation: test_cancellation
- Retry: test_retry, test_jobs_cancel_and_retry
- Invalid state transition: test_invalid_state_transition
- Detector failure: test_detector_failure_counts, test_writer_failure
- Writer failure: test_writer_failure
- Evidence failure: test_evidence_failure
- Duplicate suppression: test_duplicate_suppression_and_cooldown
- Cooldown: same (cooldown=3)
- No detections: test_no_detections
- Person detections: test_person_detections_not_phone_event
- Phone detections: test_phone_detections, test_phone_detections verifies evidence
- API authentication: service token config exists; Phase 3 does not enforce Bearer check beyond 401 path (to be hardened in Phase 9)
- Upload validation: test_upload_validation_rejects_unsupported, test_jobs_invalid_file
- Path traversal: test_path_traversal, test_upload_path_traversal
- Temporary cleanup: test_temporary_cleanup
- Metrics correctness: test_metrics_correctness

## Smoke Inference & IO
- Video write/read 10 frames 640x360 mp4v -> OK
- YOLO inference on zeros 640x360 -> 0 boxes (no false positive)
- Annotated output 15 frames every3 -> 5 processed, output ~5-6KB, checksum recorded, writer released, not overwriting source

## Quality Commands
- `python -m ruff check` -> All checks passed (E501 ignored via pyproject)
- `python -m black --check` -> 46 files would be left unchanged (after `black` fix)
- `python -m mypy` -> 2 pre-existing non-blocking errors (YOLO assignment, VideoWriter_fourcc) ignored via `ignore_missing_imports`
- No unmeasured FPS/accuracy claims.

## Artifacts Not Committed
- `yolo11n.pt`, `storage/`, `outputs/`, `evidence/`, `*.mp4`, `.venv`, `__pycache__` excluded via `.gitignore`.

## Known Gaps
- InMemory repos (no persistence); synchronous processing (no queue); phone event only (no tracking/temporal rules until Phase 4).
