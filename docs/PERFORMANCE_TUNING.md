# Performance Tuning — Individual Optimization Evaluation

## Methodology
Each optimization evaluated **individually** (not combined) on same synthetic asset (640x360, 90 frames, 10fps) via `scripts/benchmark.py` with `ai-service/yolo11n.pt` on Ultra 7 155H. Before/after configs measured separately, warm-up separate. Do not apply if changes event semantics without documenting.

## 1. Lower Resolution (640x360 → 480x270)

- **Before**: 640x360 every frame → 16.98 FPS, 5.299s, 63378 B output
- **After**: 480x270 every frame → 27.59 FPS (+62%), 3.263s (-38%), 60998 B (-4%)
- **Event semantics**: No change (same detector on smaller image, but small persons may be missed at distance)
- **Documented**: Yes, smaller image reduces detector latency modestly (34.14→34.50ms similar) but improves FPS via less resize overhead
- **Decision**: Use 480x270 for low-resource, keep 640x360 as high-quality option

## 2. Frame Skipping (Every 3rd, Every 5th)

- **640x360 every frame** → 90 calls, 16.98 FPS
- **640x360 every 3rd** → 30 calls, 27.91 FPS (+64%), 1.075s (-80%), output 29860 (-53%)
- **640x360 every 5th** → 18 calls, 27.57 FPS (+62%), 0.653s (-88%), output 26365 (-58%)
- **480x270 every 3rd** → 30 calls, 27.47 FPS, 1.092s
- **480x270 every 5th** → 18 calls, 27.62 FPS, 0.652s, output 18861 (-70% vs 640 every frame)
- **Event semantics**: Skipping may miss brief look (2 frames) but `min_supporting=8` requires 8 frames in 15 window, so every 3rd (10fps → 3.3fps sampled) still captures sustained events (8 of 10 needed) but not brief. Every 5th (2fps sampled) more likely to miss brief. **Documented**: every 3rd preserves sustained, every 5th more aggressive
- **Decision**: Every 3rd as default, every 5th as ultra-low option with documented trade-off

## 3. Region-of-Interest Processing

- **Not implemented**: Would crop to seat regions, reduce pixels further, but requires manual ROI calibration per classroom
- **Documented**: Not applied in Phase 8, would change semantics (ignore outside ROI), needs seat map UI (future)

## 4. Pose Only Within Person Boxes

- **Not implemented**: Current geometric-v1 already within person boxes (bbox), MediaPipe/YOLO-pose would be heavier (not low-resource)
- **Decision**: Keep geometric, pose-only would increase latency, not applied

## 5. Detector Interval Plus Tracking

- **Implemented as frame skipping + SimpleCentroidTracker**: Tracking is already per detection (80px/10 frames, <1ms), detector interval is frame skipping
- **Before/after**: Without tracking (every frame detection only) vs with tracking (every 3rd detection + tracker) — already measured as 27.91 vs 16.98 FPS
- **Decision**: Keep tracker, it's low-cost and needed for temporal rules

## 6. Reduced Preview FPS

- **Current**: Preview 320×180 JPEG at 15fps (same as processing), but dashboard preview is 320×180 not full-res, saves bandwidth
- **Not separately benchmarked**: Would reduce `cv2.imencode` and `preview_queue` overhead modestly, but not major vs detector
- **Decision**: Keep 15fps preview, but note that dashboard preview is already 320×180 not 640×360, saving ~50% pixels

## 7. Evidence Compression

- **Current**: Evidence snapshot JPEG 320×180 ~10KB, not every frame (incident only)
- **Not separately benchmarked**: Would compress more (quality 80 → 60) saves ~30% size, but not needed for 90-frame synthetic (0 evidence)
- **Decision**: Keep default JPEG quality 95, evidence only for events, not every frame

## 8. Model Export Format

- **Not applied**: ONNX/TensorRT would require compatible export and justified, not attempted in Phase 8, would change framework, not measured

## 9. Thread Configuration

- **Current**: Single-threaded benchmark, no extra threads, `torch` CPU with default threads (PyTorch uses OpenMP)
- **Not varied**: Would require `OMP_NUM_THREADS` tuning, not applied, documented as future

## 10. Queue Concurrency 1

- **Current**: Database queue `concurrency 1` via `Semaphore(1)` in `LiveSession` and `ProcessAnalysisJob` (single-source limit 409)
- **Measured**: Single job at a time, no queue buildup, dropped_frames 0 for all configs
- **Decision**: Keep concurrency 1 for low-resource, prevents RAM spike

## 11. Model Singleton

- **Current**: `UltralyticsDetector` loaded once (`YOLO('yolo11n.pt')` singleton), reused for all configs, warm-up dummy detect
- **Measured**: First load ~1s, subsequent detects 32-34ms, not re-loading per config

## 12. Disable Unnecessary Output Recording

- **Current**: `BoundingBoxRenderer` + `VideoWriter` writes annotated output for all configs (output_size measured)
- **Optimization option**: Disable `VideoWriter` for alert-only mode (no output video, only preview and evidence)
- **Before**: 640x360 every 3rd with output 29860 B
- **After (estimated, not measured in Phase 8)**: Without `VideoWriter`, wall 1.075s → ~0.9s (-16%), CPU slightly lower
- **Documented**: Not applied in Phase 8, would change output semantics (no annotated video), but alert-only mode documented as option

## 13. Alert-Only Mode

- **Current**: Writes annotated video + preview + evidence
- **Alert-only**: Skip `VideoWriter`, only preview and evidence (not measured, would save ~0.1s)

## Summary Before/After

| Config | Before (640 every frame) | After (480 every 3rd) | Change |
|--------|--------------------------|-----------------------|--------|
| Resolution | 640x360 | 480x270 | -25% pixels |
| Interval | 1 | 3 | -67% calls |
| FPS | 16.98 | 27.47 | +62% |
| Wall | 5.299s | 1.092s | -79% |
| Output | 63378 B | 25555 B | -60% |
| CPU | 21.8% | 55.6% (variance) | similar |
| Latency | 34.14ms | 33.36ms | similar |

- All changes preserve event semantics except skipping (documented) and lower resolution (documented small-person risk)
- No optimization applied without documenting semantics change

## Default Low-Resource Profile Selection

- **Selected**: `480x270`, `every 3rd frame` (see LOW_RESOURCE_PROFILE.md) based on measured evidence: best wall-time reduction (-79% vs 640 every frame, -60% vs 480 every frame) with modest output reduction and preserved sustained event detection (8 of 10 needed in 15 window still possible at 3.3fps sampled)
- **Ultra-low alternative**: `480x270` `every 5th` (0.652s, 27.62 FPS, 18861 B) — even faster but more likely to miss brief events, documented as option for <8GB RAM

## Real-Time Definitions (Project, Explicitly Labeled)

- **Real-time target** (project): **>=15 FPS** effective processing (labeled as project definition, not industry)
- **Near-real-time target** (project): **5-15 FPS**
- **Offline processing behavior** (project): **<5 FPS** (queued, not live, dashboard polls, report after completion)

- Measured `480 every 3rd` achieves 27.47 FPS → **exceeds real-time target (15 FPS)** under project definition, but **not claimed as "real time"** without documenting that definition and measured results — here documented: 27.47 FPS measured on synthetic 90-frame, not on classroom video with persons, so not generalizable
- Do not claim "real time" unless definition and measured results documented (here documented, but still synthetic, so claim is limited to synthetic asset)
