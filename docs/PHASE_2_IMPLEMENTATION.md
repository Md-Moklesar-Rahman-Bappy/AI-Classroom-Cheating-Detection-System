# Phase 2 Implementation

## Objective
Shared AI foundation and recorded-video baseline: typed config, secret-redaction logging, 4 input adapters, scheduler, detector, renderer, metrics, health/version endpoints, tests.

## Smoke Tests (2026-08-30, Python 3.14.0, i5-14500, 8GB, No GPU)
- `python --version` -> Python 3.14.0
- `cv2` 5.0.0, `ultralytics` 8.4.135 AGPL-3.0, `torch` 2.13.0+cpu, `fastapi` 0.136.1, `pydantic` 2.13.4, `mediapipe` 1.0.1, `psutil` 7.2.2, `numpy` 2.4.2 -> all import OK
- Video write/read 10 frames 640x360 mp4v -> wrote+read 10 OK
- YOLO inference on 640x360 zeros -> 0 boxes (no false positive), no crash
- `pydantic-settings` installed 2.15.0 for typed settings
- No blocking Python 3.14 incompatibility found; warnings: Pydantic class-based Config deprecated, FastAPI on_event deprecated (non-blocking)

## Structure Created
```
ai-service/app/config/settings.py (Settings, BaseSettings, env .env, allowed_classes [0,67])
ai-service/app/core/logging.py (SecretRedactionFilter)
ai-service/app/schemas/models.py (BoundingBox, DetectionResult, HealthResponse, VersionResponse)
ai-service/app/inputs/base.py (InputSource, VideoMetadata, FramePacket)
ai-service/app/inputs/recorded.py (RecordedVideoInput, validates existence, metadata, release)
ai-service/app/inputs/webcam.py (WebcamInput)
ai-service/app/inputs/rtsp.py (RtspStreamInput placeholder with validation, redacted logs)
ai-service/app/inputs/test_input.py (TestVideoInput)
ai-service/app/inputs/scheduler.py (FrameScheduler every-N, resize)
ai-service/app/detection/base.py (ObjectDetector protocol)
ai-service/app/detection/yolo_detector.py (UltralyticsDetector, checksum, allowed_classes)
ai-service/app/rendering/renderer.py (BoundingBoxRenderer, text+color)
ai-service/app/metrics/collector.py (MetricsCollector)
ai-service/app/api/health.py (/health, /version)
ai-service/app/main.py (FastAPI, /debug/analyze-local dev-only, restricted roots)
```

## Key Decisions
- `yolo11n.pt` via `YOLO(model_path)` loaded once at startup; `is_loaded()` check; checksum via sha256
- `process_every_n_frames` 3, `input_width` 640 `input_height` 360 configurable
- Renderer uses green for person (0), blue for phone (67), text label `class_name confidence`
- Debug endpoint requires `environment==development` and path under `ai-service/` or `samples/` or cwd; disabled in production
- Resources: VideoCapture/VideoWriter released in finally blocks

## Tests (17 passed)
- test_config: defaults, invalid n
- test_logging: secret redaction
- test_inputs: valid read 5 frames, invalid FileNotFound, skipping every3, empty file release
- test_detector: load + checksum, failure on missing weights, mapping, resize
- test_render_metrics: render, writer, metrics snapshot
- test_api: health, version, debug disabled in production

## Quality Commands
- `pytest ai-service/tests -q` -> 17 passed
- `ruff check --fix` -> 19 fixed, 0 remaining
- `black ai-service/app` -> 4 reformatted
- annotated output generation 15 frames -> 5 processed -> 4826 bytes, cleaned

## Non-Goals
- No Laravel, no tracking, no temporal rules, no custom training
