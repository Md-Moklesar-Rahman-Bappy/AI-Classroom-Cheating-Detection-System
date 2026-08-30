# System Requirements

## Verified Hardware Specification

- **Processor**: Intel(R) Core(TM) Ultra 7 155H 16c/22t (verified 2026-08-30, previous i5-14500 was earlier audit, now re-verified as Ultra 7 155H)
  - Resolution of earlier "7th Generation U-series" claim: incorrect. Verified model is i5-14500 (14-core, 20-logical-processor mainstream desktop CPU).
  - Implications: AVX2 instruction set support; x86-64 v2; suitable for CPU-only YOLO inference with OpenCV/PyTorch. Not a ultra-low-power U-series part, but 8 GB RAM remains the primary constraint.

- **RAM**: 15.5 GB total (16605540352 bytes) at benchmark, HP Optimized, heavy apps closed, 16GB RAM (previous 8GB was earlier audit, now 16GB verified)
  - Constraint: Frame skipping essential; process-every-N-frames default = 3; single-camera processing only; graceful degradation if memory pressure increases.

- **Storage**: 512 GB SSD
  - Available for: OS, applications, virtual environment, model weights, uploaded videos, annotated output, evidence snapshots, audit logs.
  - Recorded video processing: temporary extraction frames to SSD; annotated output video; incident snapshots; audit logs cleared per retention policy.

- **GPU: NVIDIA CUDA 13.2, Driver 595.95, torch 2.13.0+cpu (not used, CPU inference), benchmark shows 27.47 FPS at 480x270 every 3rd (see BENCHMARK_REPORT)
  - All YOLO inference on CPU via PyTorch.
  - Performance: Will benchmark actual FPS at 640x360 and 480x270; process-every-3rd-frame default expected ~1-3 FPS on CPU; 480x270 may provide higher FPS than 640x360.
  - No CUDA, no ROCm, no GPU acceleration.

- **Operating System**: Microsoft Windows 11 Pro
  - Development environment: Visual Studio Code
  - PowerShell 5.1 / CMD for batch operations.
  - Python 3.14.0 from official installer.

- **Python Version**: 3.14.0 (verified import for all critical packages: ultralytics, fastapi, opencv-python, numpy, pytest, ruff, black, mediapipe, psutil, pydantic, yaml, httpx)

## Installed Package Versions (Verified at Audit Time)

| Package | Version | Notes |
|---------|---------|-------|
| ultralytics | 8.4.135 | AGPL-3.0 licensed; pinned in requirements.txt < 9.0.0 |
| fastapi | 0.136.1 | ASGI API framework |
| uvicorn | 0.47.0 | ASGI server |
| opencv-python | 4.13.0.92 | Computer vision (cv2) |
| numpy | 2.4.2 | Numerical computing |
| pytest | 9.1.1 | Testing framework |
| ruff | 0.16.5 | Linter |
| black | 26.5.1 | Formatter |
| mediapipe | 1.0.1 | Pose/orientation analysis (initial; may not be used in MVP core) |
| psutil | 7.2.2 | System monitoring/memory |
| pydantic | 2.13.4 | Data validation |
| PyYAML | 6.0.1 | Configuration |
| httpx | 0.28.1 | async HTTP client |

## Operating System

- Microsoft Windows 11 Pro
- Visual Studio Code as primary IDE
- XAMPP installed (Apache + MariaDB 10.4.32; MySQL service not confirmed running)
- No GPU drivers or CUDA toolkit available

## Optional Colab / Kaggle Training

- May be used for heavier model training if local resources insufficient
- Not required for MVP (pretrained YOLO nano model used)
- If used: environment isolation; no commit of Colab notebooks or Kaggle credentials to Git
- Any Colab/training outputs stored outside public web paths

## Minimum Configuration

- Model: lightweight YOLO nano (yolo11n.pt)
- Resolution: 640x360 (configurable, starting default)
- Alternative resolution: 480x270 (low-resource alternative)
- Batch size: 1
- Process every third frame by default (process-every-N-frames = 3)
- Single active camera source
- One model instance per worker
- Incident-only evidence storage
- No unnecessary raw-video duplication

## Recommended Configuration (Post-MVP Benchmark)

To be determined after Phase 1–3 completion and actual FPS/latency/CPU measurements on i5-14500 + 8 GB RAM.

Likely adjustments:
- Frame interval may be configured to 2, 3, or 5 based on measured performance
- Resolution may default to 480x270 if 640x360 FPS too low
- CPU thread count configuration if PyTorch intra-op parallelism safe
- Memory monitor with auto-pause if free RAM < 1 GB

## Storage Planning

| Item | Estimated Size | Retention |
|------|---------------|-----------|
| Model weights (yolo11n.pt) | ~5.4 MB | Not version-controlled; referenced via requirements.txt |
| Uploaded video asset | Variable (user dependent) | Outside public web paths; excluded from Git |
| Extracted frames (temporary) | Variable; process-every-3rd-frame reduces count | Cleanup after job completion |
| Annotated output video | Similar size to input | User-downloadable; outside public web paths |
| Evidence snapshots | ~0.5 MB per incident | Retention policy; secure deletion |
| Audit logs | ~1 KB per action | Retention policy; never delete until audit window closed |
| Processing metrics | ~1 KB per job | Retained for evaluation |

## Camera Requirements

- **Primary**: EZVIZ CP1 Lite (2MP Pan and Tilt Wi-Fi Dome IP Camera)
  - RTSP/ONVIF compatibility: **UNVERIFIED** (not assumed; camera-source abstraction must support fallback)
  - If RTSP unavailable: recorded video mode remains fully usable; live pipeline testable using local webcam or other compatible source
  - Camera credentials: never committed to Git, stored in source code, included in logs, returned through public API responses, or displayed after saving

- **Fallback/Testing**: Local webcam (built-in or USB)
  - Used for live pipeline testing when EZVIZ stream unavailable
  - Camera-source abstraction must support webcam input adapter

- **Supported input sources** (via abstraction layer):
  - Local webcam
  - Video file (recorded mode)
  - RTSP URL (where supported by firmware)
  - HTTP-compatible stream (where supported)
  - Test or simulated stream

- **Camera credentials protection**:
  - Stored in .env file excluded from version control
  - Never returned through public API responses
  - Never logged in plaintext
  - Encrypted or protected via OS secrets mechanism
  - Abstraction layer isolates credential details from engine

## Explicitly Unverified Camera-Stream Capabilities

- EZVIZ CP1 Lite RTSP endpoint availability and stream format
- ONVIF compliance of installed firmware
- Maximum resolution and frame rate of live stream
- Audio capture capability (not required for MVP)
- Pan/tilt control via API
- Authentication method for RTSP/HTTP stream
- Simultaneous multiple-session support

All camera-stream capabilities will be tested during Phase 2 (Shared AI Foundation) and documented in CAMERA_SETUP.md. Until verified, the system assumes recorded video mode as primary and local webcam as live-mode fallback.

## Python 3.14 Compatibility

All critical packages verified to import successfully on Python 3.14.0:
- ultralytics 8.4.135
- fastapi 0.136.1
- opencv-python 4.13.0.92
- numpy 2.4.2
- pytest 9.1.1
- mediapipe 1.0.1
- psutil 7.2.2
- pydantic 2.13.4
- ruff, black (format/lint)
- PyYAML 6.0.1
- httpx 0.28.1

No verified incompatibilities blocking development. If future incompatibility discovered, Python 3.11 or pinned LTS version will be documented as alternative.

## Dependency Direction (Explicit)

- Python AI service → No downstream dependence on Laravel dashboard
- Laravel dashboard → API calls to AI service (versioned internal API)
- AI service → ultralytics (AGPL-3.0, pinned)
- AI service → fastapi, uvicorn (Python standard ASGI stack)
- AI service → opencv-python, numpy (computer vision)
- AI service → psutil (system monitoring)
- AI service → pydantic, yaml (configuration/validation)
- AI service → mediapipe (pose/orientation; optional in MVP)
- Database → SQLAlchemy only if independently required (not required for MVP core)
- Dashboard → MySQL (for data persistence); may use SQLite for development/testing MVP subset

## Low-Resource Profile

Default MVP configuration tuned for 8 GB RAM, no GPU, i5-14500 CPU:

| Setting | Value | Rationale |
|---------|-------|-----------|
| Model | yolo11n.pt (nano) | Lightest YOLOv11 checkpoint |
| Resolution | 640x360 (default); 480x270 (alternative) | Fits screen; reduces pixel count ~35% vs 1080p |
| Batch size | 1 | Single-camera processing |
| Process every N frames | 3 (default) | Reduces CPU load ~3x; configurable to 1, 2, 5 |
| Input format | BGR (OpenCV) → RGB (model) | Standard YOLO preprocessing |
| Tracker | Not yet introduced (Phase 2+) | Will add ByteTrack/DeepSORT after MVP baseline |
| Evidence | Incident snapshots only | No continuous recording |
| CPU thread count | Not configured (PyTorch default) | Will benchmark and configure if safe |
| Queue concurrency | 1 (single camera) | Minimal implementation |
| Maximum active camera count | 1 | One-camera minimum |

These are configuration starting points. Do not describe as proven optimal. Benchmark actual behavior before selecting final defaults.
