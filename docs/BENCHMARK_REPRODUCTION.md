# Benchmark Reproduction

## Prerequisites

- Hardware: Intel Ultra 7 155H 16c/22t, 16GB RAM, NVIDIA CUDA 13.2 (torch CPU), Windows 10 Pro 2009, Python 3.14.3, yolo11n.pt checksum `0ebbc80d...`, opencv 5.0.0, ultralytics 8.4.135, torch 2.13.0+cpu, psutil 7.2.2, power HP Optimized, close chrome/Code before run
- Repo: `git clone` with `ai-service/yolo11n.pt` (not committed, download via `pip` or manual), `research/` not containing sensitive videos

## Steps

1. **Install**
   ```bash
   pip install -r ai-service/requirements.txt
   pip install -r ai-service/requirements-dev.txt  # for ruff, black, mypy
   ```

2. **Generate synthetic asset** (already in `scripts/benchmark.py:generate_synthetic_video`):
   ```bash
   python -c "import cv2, numpy as np; w=cv2.VideoWriter('tmp.mp4', cv2.VideoWriter_fourcc(*'mp4v'), 10, (640,360)); [w.write(np.zeros((360,640,3),dtype='uint8')) for _ in range(90)]; w.release()"
   ```
   - Characteristics: 640x360, 10fps, 90 frames (9s), moving 80x80 gray rectangle + white circle, no PII, checksum `3f24d29b154464cd`

3. **Warm-up** (separate)
   ```bash
   python -c "import sys; sys.path.insert(0,'ai-service'); from app.detection.yolo_detector import UltralyticsDetector; import numpy as np; d=UltralyticsDetector('ai-service/yolo11n.pt'); d.detect(np.zeros((360,640,3),dtype='uint8'))"
   ```
   - Not counted in measured wall time

4. **Run benchmark** (all configs):
   ```bash
   python scripts/benchmark.py --output research/results/benchmark_results.json --manifest research/experiments/benchmark_manifest.json
   # Or specific: --widths 640 480 --heights 360 270 --intervals 1 3 5
   ```
   - Output: `research/results/benchmark_results.json` (machine-readable, sanitized paths) and `research/experiments/benchmark_manifest.json`
   - Do not mix training: only inference, no training benchmarks

5. **Verify**
   - Check `research/results/benchmark_results.json` contains `hardware`, `asset`, `results` with 7 configs (6 recorded + 1 live if webcam), each with `wall_clock_duration_seconds`, `effective_processing_fps`, `detector_latency_avg_ms`, etc., no `C:\` paths, only basenames
   - Check `research/experiments/benchmark_manifest.json` contains `reproducible: true`, `warm_up` note

6. **Compare**
   ```bash
   python -c "import json; d=json.load(open('research/results/benchmark_results.json')); print([r['effective_processing_fps'] for r in d['results']])"
   ```

## No Sensitive Data

- Do not commit `video` files (`*.mp4`, `*.avi` ignored via `.gitignore`)
- Results `research/results/*.json` may be committed only if `video_path` is basename (sanitized via `sanitize_path`), no `C:\xampp\...` or credentials

## Troubleshooting

- If `cv2.VideoCapture(0)` fails, live mode not verified (expected for EZVIZ), use `test` source
- If `yolo11n.pt` missing, download via `python -c "from ultralytics import YOLO; YOLO('yolo11n.pt')"` (will download 5.6MB to `ai-service/`)
- If `psutil` CPU% is 0, ensure `psutil.cpu_percent(interval=None)` called before/after

## Expected Output (from actual run 2026-08-30 21:28)

- 640x360 every frame: 16.98 FPS, 5.299s, 63378 B
- 480x270 every 3rd: 27.47 FPS, 1.092s, 25555 B (selected low-resource)
- See `BENCHMARK_REPORT.md` for full table
