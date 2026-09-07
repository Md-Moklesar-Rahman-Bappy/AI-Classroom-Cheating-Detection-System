# Technical Supplement — Consolidated
Date: 2026-09-07

This supplement consolidates detector/tracker/orientation/benchmark internals that were previously scattered across 10+ docs. Canonical docs remain; this is a reading guide.

## 1. Orientation Method
**Canonical**: `ORIENTATION_METHOD.md`, evaluation in `ORIENTATION_METHOD_EVALUATION.md`
- Geometric method `geometric-v1` using thresholds `left -0.15 / right 0.15 / backward_aspect 1.8`
- Window 15, min_supporting 8, max_missing 4, cooldown 45
- See `ORIENTATION_METHOD.md` for per-frame cue and limitations (head size, camera angle).

## 2. Tracking Design
**Canonical**: `TRACKING_DESIGN.md` (+ appendix from `TRACKING_FAILURE_REPORT.md`)
- Centroid tracker `SimpleCentroidTracker(max_distance 90, max_missing 15)` after accuracy tuning
- Greedy nearest within 90px in 640×360, no Re-ID, `process_every_n_frames=3`
- S3/B4 stale bbox behavior documented in `AUDIT_REPORT.md` §5

## 3. Cross-Service Video Transfer
**Archived root cause**: `docs/archive/CROSS_SERVICE_VIDEO_TRANSFER_ROOT_CAUSE.md`
- Laravel `VideoAsset` → FastAPI `POST /api/v1/jobs/recorded` via `AiServiceClient`; file validation via `VideoCapture` readability.

## 4. Performance Tuning & Benchmarks
**Canonical**: `PERFORMANCE_TUNING.md`, `BENCHMARK_REPORT.md`, `BENCHMARK_REPRODUCTION.md`, `LOW_RESOURCE_PROFILE.md`, `MODEL_BASELINE.md`, `MODEL_CARD.md`, `REPRODUCIBILITY.md`
- Metrics: Precision/Recall/F1/mAP/FPS/latency/CPU/Mem via `scripts/benchmark.py`
- Low-resource profile `window_size 15` for CPU-only deployment

## 5. Video I/O & Streaming
**Canonical**: `VIDEO_IO_VALIDATION.md`, `STREAMING_ARCHITECTURE.md`, `CAMERA_SETUP.md`, `CAMERA_MANAGEMENT_GUIDE.md`, `LIVE_SURVEILLANCE_MODE.md`, `RECORDED_VIDEO_MODE.md`

## 6. Event/Behavior Engine
**Canonical**: `EVENT_SYSTEM_V2.md`, `EVENT_ENGINE_V2.md`, `TRACK_TO_EVENT_MAPPING.md`, `TEMPORAL_EVENT_RULES.md`, `BEHAVIOR_EVENT_LIMITATIONS.md`

## Reading Order for Viva
1. `ARCHITECTURE.md` → `DATABASE_DESIGN.md` → `API_CONTRACT.md`
2. This supplement (orientation/tracking)
3. `EVENT_TAXONOMY_V2.md` + `EVIDENCE_ANNOTATION_SYSTEM.md`
4. `BENCHMARK_REPORT.md` + `AUDIT_REPORT.md` (consolidated audits)
5. `SECURITY_AUDIT.md` + `AUTHORIZATION_MATRIX.md`

All sources preserved; no deletions.
