# Phase 4 Test Report

## Environment
- Python 3.14.3, torch 2.13.0+cpu, ultralytics 8.4.135, fastapi 0.141.1, pydantic 2.13.4, mediapipe 1.0.1, opencv 5.0.0, numpy 2.4.6, psutil 7.2.2
- CPU Ultra 7 155H 16c/22t 16GB RAM, GPU NVIDIA present but not used (CPU inference)
- Warnings: Pydantic class-based Config deprecated, FastAPI on_event deprecated (non-blocking)

## Tests Run
`python -m pytest ai-service/tests -v` -> **56 passed** (43 Phase 2/3 + 13 Phase 4), 5 warnings

### Phase 4 Fixtures (13 tests in test_tracking_orientation.py)
- Stable forward (15 frames forward) -> no event, quality high/medium
- Brief left look (2 frames) -> no repeated event
- Repeated left look (15 left) -> 1 event at frame >=9, explanation present, supports >8/15
- Repeated right look (15 right) -> 1 event "Repeated Looking Right"
- Looking backward (15 backward, min_supporting 4) -> 1 event "Looking Backward"
- Missing landmarks (small bbox 10x20 -> low quality, zero bbox -> handled)
- Occlusion (track missing 3 > max_missing 2 -> ID removed)
- Track switching (det at 100 vs 300 distance 200 >80 -> 2 tracks, old retained)
- Reappearance (missing 5 <10, reappear 105 -> same ID)
- Seat departure (absence 5 >=5 -> 1 Leaving Seat, explanation contains "absence")
- Cooldown & duplicate suppression (window10 min5 cooldown10: first at 4, 10-13 suppressed, next at 20-29)
- Concurrent tracks (2 persons, only track1 left -> 1 left event for track1)
- Insufficient evidence (uncertain/low -> true, forward/high -> false)

All fixtures synthetic bbox, no unauthorized identifiable data.

### Existing Pipeline Tests Still Passing
- Valid e2e, invalid/empty, cancellation, retry, detector/writer/evidence failure, suppression/cooldown, no detections, person/phone, traversal, temp cleanup, metrics, API lifecycle, CLI.

## Smoke & Runtime Observations (actual, not invented)
- Geometric orientation <1ms/frame (bbox arithmetic) vs YOLO detection 1.29s/frame CPU on 640x360 zeros.
- SimpleCentroidTracker <1ms/frame, no extra dependencies, 80px threshold.
- Temporal engine buffer 15, no memory growth beyond window.
- Annotated output generation 15 frames every3 -> 5 processed, output ~5-6KB, colors: green forward, amber accumulating, red event, blue phone, gray insufficient verified via renderer code.
- Brief movement (2 left) did not emit; sustained (15 left) did emit with explanation.

## Upload & Job Integration
- Recorded pipeline now runs tracker+orientation+temporal per scheduled frame; metrics include `track_count`, `behavior_event_count`, `orientation_method`, `config_version`.
- Evidence limited to best representative frame per event (not every frame), with `event_id` link.
- Resources released: VideoCapture/VideoWriter in finally.

## Quality Commands
- `ruff check` -> All checks passed (E501 ignored)
- `black --check` -> 46 files unchanged (after fix)
- `mypy` -> 2 pre-existing non-blocking errors (YOLO assignment, VideoWriter_fourcc)
- No unmeasured FPS/accuracy claims.

## Limitations Documented
- Leaving seat is proxy (track missing), not true seat ROI -> marked partially implemented.
- Orientation via aspect is proxy, not true yaw.
- SimpleCentroid may switch IDs on crossing.

## Not Committed
- `yolo11n.pt` (local, ignored), `outputs/`, `evidence/`, `storage/`, `*.mp4`, `__pycache__`.

## Acceptance Criteria
- Anonymous tracks generated (temporary IDs, no embeddings) -> verified.
- Orientation observations contain quality/uncertainty -> verified (quality high/medium/low, insufficient_reason).
- Brief movement does not create repeated events -> verified (2 left no event, 15 left emits).
- Temporal events have explainable supporting info -> verified (explanation with thresholds, config_version).
- Duplicate suppressed -> verified (cooldown 10-13 suppressed, next at 20+).
- Missing evidence -> uncertain state -> verified (small bbox low, first obs uncertain).
- Recorded output correct text/colors -> verified via renderer (Green/Amber/Red/Blue/Gray).
- Tests pass, actual CPU/runtime documented without fabricated claims -> done.
