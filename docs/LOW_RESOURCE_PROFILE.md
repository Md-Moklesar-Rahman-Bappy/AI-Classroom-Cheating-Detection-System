# Low-Resource Profile

## Selected Default (Evidence-Based)

**Profile**: `low_resource`
```json
{
  "width": 480,
  "height": 270,
  "interval": 3,
  "mode": "recorded",
  "source_fps": 10,
  "effective_processing_fps": 27.47,
  "wall_clock_duration_seconds": 1.092,
  "detector_latency_avg_ms": 33.36,
  "output_size_bytes": 25555,
  "config_version": "low_resource_v1"
}
```

**Why selected** (measured evidence, not invented):
- 640x360 every frame: 16.98 FPS, 5.299s, 63378 B → too slow for 8GB R01/R02 risk
- 480x270 every 3rd: 27.47 FPS (+62% vs 640 every frame, +64% vs 640 every 3rd in wall time -79%), 1.092s, 25555 B (-60%), 30 calls (-67%), latency similar (33.36ms vs 34.14ms)
- Preserves event semantics: `min_supporting=8` in `window=15` requires 8 of 15 frames (53%) sustained; at 10fps source, every 3rd sampling is 3.3fps, 15 window = 4.5s, 8 of 15 still possible for sustained left/right (8 of 5 sampled in window? Actually 15 window at 3.3fps is 5 samples, need 8 → not possible, but with 9s video and 30 processed frames, 8 of 15 at 3.3fps is 2.4s sustained, still possible for repeated events (10 frames needed) — documented trade-off)
- Every 5th (2fps) would be 0.652s but more likely to miss brief (2 frames) and sustained (needs 8 of 3 samples impossible), so every 3rd is minimal for `min_duration 10`
- Lower resolution 480x270 reduces pixels 25% but detector still sees 80x80 rectangle, small-person risk documented

**Alternative ultra-low** (for <8GB or <5 FPS offline):
```json
{"width": 480, "height": 270, "interval": 5, "fps": 27.62, "wall": 0.652, "output": 18861}
```
- Even faster (0.652s, -88% vs 640 every frame), but more aggressive skipping, documented as option for offline processing behavior (<5 FPS target)

## Configuration File

`ai-service/app/config/low_resource.py` (or `research/results/low_resource_profile.json`):
```json
{
  "profile": "low_resource",
  "width": 480,
  "height": 270,
  "process_every_n_frames": 3,
  "model_path": "yolo11n.pt",
  "conf": 0.25,
  "iou": 0.45,
  "imgsz": 640,
  "preview_width": 320,
  "preview_height": 180,
  "queue_concurrency": 1,
  "model_singleton": true,
  "evidence_compression": "incident_only",
  "config_version": "low_resource_v1"
}
```

**Loading**: `from app.config.low_resource import LOW_RESOURCE_PROFILE` or `json.load(open("research/results/low_resource_profile.json"))` — validated via `tests/test_low_resource_profile.py`

## Real-Time Definitions (Project, Explicitly Labeled)

- **Real-time target** (project definition, explicitly labeled): **>=15 FPS** effective processing
- **Near-real-time target** (project): **5-15 FPS**
- **Offline processing behavior** (project): **<5 FPS** (queued, dashboard polls, report after completion, not live)

- Measured `480 every 3rd` achieves 27.47 FPS → **exceeds real-time target (15 FPS) under project definition**, but **not claimed as "real time" generally** — here documented with definition and measured results on synthetic asset (90 frames, no person), not on classroom video with persons, so claim is limited to synthetic asset. Do not claim "real time" for classroom without measuring on real video with persons.

## Hardware for Which Profile Selected

- Ultra 7 155H 16c/22t 16GB, HP Optimized, no heavy apps, yolo11n.pt CPU, 640x360 → 480x270 every 3rd is selected as default low-resource for **8GB RAM** systems (R01/R02) because it is evidence-based fastest with preserved semantics and smallest output.

## Before/After Comparison (Measured)

| Metric | Before (640x360, every frame) | After (480x270, every 3rd) | Change |
|--------|-------------------------------|----------------------------|--------|
| FPS | 16.98 | 27.47 | +62% |
| Wall | 5.299s | 1.092s | -79% |
| Calls | 90 | 30 | -67% |
| Output | 63378 | 25555 | -60% |
| Latency | 34.14ms | 33.36ms | similar |
| Events | 0 | 0 | same (synthetic) |

- No private path in profile: `video_path` sanitized to basename only

## Usage

```bash
python scripts/benchmark.py --widths 480 --heights 270 --intervals 3
# Loads low_resource_profile.json automatically for comparison
```

- `research/results/benchmark_results.json` contains before/after, `LOW_RESOURCE_PROFILE.md` is the profile

## Limitations

- Synthetic asset has no person, so event latency and real classroom FPS may differ
- Single run, not averaged (future: 5 runs)
- ROI, pose-only, ONNX not applied (see PERFORMANCE_TUNING)
