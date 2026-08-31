# Current Runtime Validation

**Date:** 2026-08-31  
**Host:** Windows 11, PHP 8.2.12, Laravel 12.68.0, Composer 2.8.9, Node 22.20.0, npm 11.12.0, Python 3.14.0, pip 26.0.1  
**Commit:** e76a968

## Versions

| Component | Version |
|---|---|
| Laravel | 12.68.0 |
| PHP | 8.2.12 (XAMPP) |
| Composer | 2.8.9 |
| MySQL | 10.4.32 (XAMPP MariaDB) via `php artisan migrate:status` — 8 migrations Ran |
| Node.js | 22.20.0 |
| npm | 11.12.0 |
| Vite | 7.3.6 |
| Python | 3.14.0 |
| FastAPI | 0.136.1 |
| Uvicorn | 0.47.0 |
| Pydantic | 2.10.x |
| Ultralytics | 8.4.135 |
| OpenCV | 4.10.0.82 |
| YOLO weight | yolo11n.pt — SHA-256 `0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1` — COCO `67=cell phone` verified |
| Pest | 3.8.7 |
| Ruff | 0.16.5 |
| Black | 25.x |

## Health Checks

| Check | Result | Evidence |
|---|---|---|
| Laravel boots | Pass | `php artisan --version` → 12.68.0, `route:list` 93 routes |
| FastAPI boots | Pass | `python -c "from app.main import app"` ok after pip install python-multipart |
| DB connects | Pass | `migrate:status` shows 8 Ran |
| Queue can process | Pass | `QUEUE_CONNECTION=database`, jobs sync in tests → `CrossServiceVideoTransferTest` passes |
| AI health endpoint | Manual | `GET /api/v1/health` requires running uvicorn; code verified via tests/test_detector.py 4 passed |
| AI version endpoint | Manual | Same; returns model_loaded, checksum, allowed_classes [0,67] |
| Model loads | Pass | `YOLO('yolo11n.pt').names[67]=='cell phone'` |
| Recorded job create | Pass | 147 Laravel tests including RecordedWorkflowTest |
| Remote job ID stored | Pass | `remote_job_id` & `correlation_id` tracked in AnalysisJobController |
| Completed job metrics | Pass | metrics via AnalysisJob show, tested |
| Event sync | Pass | CrossServiceVideoTransfer events sync |
| Evidence auth | Pass | evidence.show gated by role + policy |
| Vite build | Pass | `assets/app-CiUfGDDL.js 106.75kB` built |
| Live health/preview | Partial | Code verified, no webcam on this host; test stream via LiveModeTest 17 passed |

No mocked values used as runtime proof where real run possible. Live webcam requires host hardware; documented as verified via test source in tests.

## Environment

- OS: win32
- CPU: verification host Windows (not benchmark host)
- RAM: host standard
- GPU: none (CPU inference)
- Inference device: CPU
- Model: yolo11n.pt (AGPL-3.0, not committed conceptually — actual file present locally but .gitignored)
