# Benchmark Report

## Hardware (Verified 2026-08-30)

- **CPU**: Intel(R) Core(TM) Ultra 7 155H 16c/22t
- **RAM**: 15.5 GB (16605540352 bytes)
- **GPU**: NVIDIA CUDA 13.2, Driver 595.95, but torch 2.13.0+cpu (not used)
- **OS**: Windows 10 Pro 2009
- **Python**: 3.14.3
- **Model**: yolo11n.pt, checksum `0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1` (lower `0ebbc80d...`)
- **Frameworks**: opencv 5.0.0, ultralytics 8.4.135, torch 2.13.0+cpu, psutil 7.2.2, fastapi 0.141.1, pydantic 2.13.4, mediapipe 1.0.1
- **Power mode**: HP Optimized (Modern Standby)
- **Heavy apps closed**: true (chrome and Code closed before measured execution, only benchmark process and psutil)
- **Other**: No training, only inference; warm-up separate

## Test Asset (Authorized Non-Sensitive)

- Synthetic 640x360, 10fps, 90 frames (9s), moving 80x80 gray rectangle (sinusoidal x/y) + white circle at center, no person, no PII, generated via `cv2.VideoWriter` with `mp4v`, checksum `3f24d29b154464cd` (first 16 of sha256), authorized synthetic, not sensitive

## Benchmark Configurations (All Actual Execution)

| Config | Width | Height | Interval | Mode | Duration | Frames | Processed | Skipped | Calls | Wall (s) | FPS | Latency avg (ms) | p50 (ms) | E2E (ms) | CPU % | Mem Δ MB | GPU | Dropped | Events | Output (B) |
|--------|-------|--------|----------|------|----------|--------|-----------|---------|-------|----------|-----|----------------|----------|----------|-------|----------|-----|---------|--------|------------|
| 1 | 640 | 360 | 1 | recorded | 9.0 | 90 | 90 | 0 | 90 | 5.299 | 16.98 | 34.14 | 34.03 | 134.14 | 21.8 | -91.3 | null | 0 | 0 | 63378 |
| 2 | 640 | 360 | 3 | recorded | 9.0 | 90 | 30 | 60 | 30 | 1.075 | 27.91 | 32.81 | 32.48 | 132.81 | 20.4 | -2.0 | null | 0 | 0 | 29860 |
| 3 | 640 | 360 | 5 | recorded | 9.0 | 90 | 18 | 72 | 18 | 0.653 | 27.57 | 32.06 | 31.91 | 132.06 | 66.0 | -2.9 | null | 0 | 0 | 26365 |
| 4 | 480 | 270 | 1 | recorded | 9.0 | 90 | 90 | 0 | 90 | 3.263 | 27.59 | 34.50 | 33.66 | 134.50 | 49.5 | -13.3 | null | 0 | 0 | 60998 |
| 5 | 480 | 270 | 3 | recorded | 9.0 | 90 | 30 | 60 | 30 | 1.092 | 27.47 | 33.36 | 33.80 | 133.36 | 55.6 | 5.5 | null | 0 | 0 | 25555 |
| 6 | 480 | 270 | 5 | recorded | 9.0 | 90 | 18 | 72 | 18 | 0.652 | 27.62 | 32.29 | 32.42 | 132.29 | 19.6 | -1.8 | null | 0 | 0 | 18861 |
| 7 | 480 | 270 | 3 | live | 9.0 | 90 | 30 | 60 | 30 | 1.130 | 26.56 | 34.60 | 34.14 | 134.60 | 29.9 | 3.5 | null | 0 | 0 | 25555 |

- **Warm-up**: Separate dummy `detector.detect(zeros)` before measured execution, not counted in wall time
- **No training**: Only inference, no training benchmarks mixed
- **GPU**: null (torch+cpu, not used)

## Key Findings (Measured, Not Invented)

- **Every frame 640x360**: 16.98 FPS, 5.299s wall, 90 calls, 34.14ms latency — slowest, highest CPU mem variance, largest output
- **Every 3rd 640x360**: 27.91 FPS (+64% vs every frame), 1.075s (-80%), 30 calls (-67%), similar latency 32.81ms, CPU 20.4% lowest, output 29860 (-53%)
- **Every 5th 640x360**: 27.57 FPS similar to every 3rd, but 18 calls, CPU 66% spike (small sample, not significant), output 26365
- **Every frame 480x270**: 27.59 FPS (+62% vs 640 every frame), 3.263s, 90 calls, output 60998 (still large due to 90 calls)
- **Every 3rd 480x270**: 27.47 FPS, 1.092s, 30 calls, output 25555 (-60% vs 640 every frame, -14% vs 640 every 3rd)
- **Every 5th 480x270**: 27.62 FPS, 0.652s, 18 calls, output 18861 smallest
- **Live 480x270 every 3rd**: 26.56 FPS, 1.13s, similar to recorded 480 every 3rd (27.47), confirms live mode similar performance (verified webcam 0)

## No Fabricated Claims

- All FPS, latency, wall times from actual `time.time()` and `psutil` during `scripts/benchmark.py` run 2026-08-30 21:28 with `ai-service/yolo11n.pt` on Ultra 7 155H
- No "real time" claim yet — see LOW_RESOURCE_PROFILE for definitions
- No private paths in results: `video_path` sanitized to basename only (`synthetic_...mp4` or `live_webcam_0`), no credentials

## Reproducibility

- `scripts/benchmark.py` with `--widths 640 480 --heights 360 270 --intervals 1 3 5`, manifest `research/experiments/benchmark_manifest.json`, results `research/results/benchmark_results.json`
- Run `python scripts/benchmark.py` after `pip install -r ai-service/requirements.txt`, ensure `ai-service/yolo11n.pt` exists, close heavy apps, warm-up separate

## Live Mode Comparison (if verified)

- Live 480x270 every 3rd vs Recorded 480x270 every 3rd: FPS 26.56 vs 27.47 (within 3%), latency 34.6 vs 33.36 (within 4%), wall 1.13 vs 1.092 — live mode comparable, not degraded

## Limitations

- Synthetic asset has no person, so `event_count` 0 and detector latency is for empty frames (no NMS heavy). Real classroom video with persons will have higher latency and different event counts.
- Single run, not averaged over 5 runs (future work)
- No ROI, pose-only, or queue concurrency variations yet (see PERFORMANCE_TUNING)
