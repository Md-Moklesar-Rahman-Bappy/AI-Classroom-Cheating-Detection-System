# AI Service

Python FastAPI service for recorded-video baseline detection.

## Setup

```bash
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8001
```

## Endpoints (Phase 2)

- `GET /api/v1/health`
- `GET /api/v1/version`
- `POST /api/v1/debug/analyze-local?path=...` (development only)

Uses Ultralytics YOLO AGPL-3.0 (see THIRD_PARTY_NOTICES.md).
