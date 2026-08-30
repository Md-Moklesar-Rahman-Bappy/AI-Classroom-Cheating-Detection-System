# API Contract

## Versioning

- Base path: `/api/v1`
- Version in URL; breaking changes require `v2`
- `Accept: application/json`; `Content-Type: application/json` unless multipart for video upload
- All timestamps ISO 8601 UTC (`YYYY-MM-DDTHH:MM:SSZ`)

## Authentication

- **Laravel <-> AI service (service-to-service)**: Bearer token in `Authorization: Bearer <AI_SERVICE_TOKEN>`; token stored in `.env` (`AI_SERVICE_TOKEN`), never in Git or logs. Token rotated via env update.
- **User -> Laravel**: Session cookie + CSRF; Laravel Sanctum or built-in auth; all endpoints behind `auth` middleware.
- AI service does not have user tables; it trusts Laravel's service token plus `X-User-Id` and `X-Role` headers for audit correlation only after Laravel has authorized.
- No camera credentials in query strings or logs.

## Correlation IDs

- Client may send `X-Correlation-Id: <uuid>`; server generates one if missing.
- Returned in every response header `X-Correlation-Id`.
- Included in structured logs and audit entries.

## Error Structure

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Human-readable message without secrets.",
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "details": { "field": ["reason"] }
  }
}
```

Codes: `VALIDATION_ERROR` (422), `AUTHENTICATION_REQUIRED` (401), `FORBIDDEN` (403), `NOT_FOUND` (404), `CONFLICT` (409), `RATE_LIMITED` (429), `SERVICE_UNAVAILABLE` (503), `INTERNAL_ERROR` (500).

Secrets redacted: passwords, tokens, RTSP URLs with credentials, raw auth headers, full request bodies with secrets.

## Endpoints

### Health

`GET /api/v1/health`

Auth: optional (public with rate limit) or service token.

Response 200:
```json
{ "status": "ok", "model_loaded": true, "version": "1.0.0", "uptime_seconds": 1234 }
```

### Model List

`GET /api/v1/models`

Auth: service token. Response 200:
```json
{ "models": [{ "id": "yolo11n-v1", "name": "yolo11n.pt", "version": "11n", "classes": ["person","cell phone"], "checksum_sha256": "abc...", "license": "AGPL-3.0" }] }
```

### Recorded Job Creation

`POST /api/v1/jobs/recorded`

Auth: service token (`Authorization: Bearer <token>`), `X-Correlation-Id` header. Body: `multipart/form-data` with `file` (video) + metadata fields.

**Request** (multipart, authenticated):
- `file` (required): video file binary, `video/mp4` etc., max size `max_upload_mb` (500), validated via `python-magic`/`file` and `VideoCapture` readability, not just extension
- `original_filename` (required): original name without path, sanitized (no `..`/`/`/`\`), used only for suffix and logging
- `mime_type` (optional): `video/mp4` etc., validated against allowed list
- `file_size` (optional): bytes, must match `len(content)` if provided
- `file_checksum` (optional): SHA256 hex 64, verified if provided
- `model_version` or `model_version_id` (optional): `yolo11n.pt` etc., validated against `model_versions` table
- `config` (optional): JSON string `{"input_width":640,"input_height":360,"process_every_n_frames":3}`
- `correlation_id` (optional): header `X-Correlation-Id` or form field, UUID, logged
- `dashboard_job_id` (optional): `analysis_jobs.id` from Laravel for idempotency, validated as integer

**Example** (`curl`):
```bash
curl -X POST http://127.0.0.1:8001/api/v1/jobs/recorded \
  -H "Authorization: Bearer <token>" -H "X-Correlation-Id: <uuid>" \
  -F "file=@video.mp4;type=video/mp4" \
  -F "original_filename=video.mp4" -F "mime_type=video/mp4" -F "file_size=102400" \
  -F "file_checksum=abc..." -F "model_version=yolo11n.pt" -F "config={\"input_width\":640}" -F "dashboard_job_id=123"
```

**Response 201**:
```json
{ "job_id": "uuid", "remote_job_id": "uuid", "status": "pending", "correlation_id": "uuid", "config": {"input_width":640}, "progress_percent": 0 }
```
- `job_id` is the AI-service's `remote_job_id`, returned to Laravel for `remote_job_id` saving
- No absolute paths, no credential echo

**Security**: Service auth, request-size limit (500MB), allowed MIME (`video/mp4`, `video/avi`, `video/quicktime`, `video/x-msvideo`, `video/x-matroska`), video readability via `VideoCapture` (not just extension), safe generated filename (`uuid+ext` in `tempfile`), controlled input directory (`tempfile.gettempdir()`), no `..`/`/`/`\` in original_filename, no trust in `original_filename` for path, no arbitrary local path parameter, no credential logging, correlation ID logged, temp cleanup on failure, idempotency via `correlation_id` or `dashboard_job_id` (duplicate within 24h returns same `job_id` 201, not new).

**Validation**: 422 if `file` missing/empty, `original_filename` contains traversal, unsupported type, unreadable video, `file_size` mismatch, `checksum` mismatch.
**Errors**: 401 auth, 413 too large, 422 validation, 500 internal (sanitized).

### Job Status

`GET /api/v1/jobs/{job_id}`

Auth: service token. Response 200:
```json
{ "job_id": "uuid", "status": "processing", "progress_percent": 42, "frames_processed": 420, "frames_total": 1000, "started_at": "2026-08-30T10:00:00Z", "failure_reason": null }
```

Statuses: `pending|queued|processing|paused|cancelled|failed|completed`.

### Job Cancellation

`POST /api/v1/jobs/{job_id}/cancel`

Auth: service token. Idempotent: cancelling already cancelled returns 200 with current status. Response 200: `{ "job_id": "uuid", "status": "cancelled" }`.

### Job Retry

`POST /api/v1/jobs/{job_id}/retry`

Auth: service token. Only from `failed` or `cancelled`. Creates new job or re-queues; returns 201 with new `job_id` if new record, or 200 if re-queued. Idempotency key `Idempotency-Key` header recommended.

### Live Start

`POST /api/v1/live/start`

Auth: service token. Body: `{ "exam_session_id": "uuid", "camera_source_id": "uuid", "config": { "input_width":640, "input_height":360, "process_every_n_frames":3 } }`
Response 201: `{ "live_session_id": "uuid", "status": "starting" }`
Errors: 404 camera not found, 409 already running for session, 503 camera unreachable.

### Live Stop

`POST /api/v1/live/{session_id}/stop`

Auth: service token. Idempotent. Response 200: `{ "session_id": "uuid", "status": "stopped" }`

### Live Health

`GET /api/v1/live/{session_id}/health`

Auth: service token. Response 200:
```json
{ "session_id":"uuid","status":"running","fps_processing":2.1,"fps_source":30,"latency_ms":320,"dropped_frames":5,"reconnect_count":0,"queue_size":2 }
```

### Event Retrieval

`GET /api/v1/events?exam_session_id=uuid&analysis_job_id=uuid&event_type=B1&review_status=pending&page=1&per_page=20`

Auth: service token. Response 200:
```json
{ "data": [{ "id":"uuid","exam_session_id":"uuid","analysis_job_id":"uuid","temporary_track_id":3,"event_type":"B1","event_status":"active","started_at":"...","ended_at":"...","confidence":0.82,"review_status":"pending" }], "meta": { "total": 42 } }
```

### Single Event

`GET /api/v1/events/{event_id}` -> 200 event detail or 404.

### Metrics

`GET /api/v1/metrics/{job_id}`

Auth: service token. Response 200:
```json
{ "job_id":"uuid","source_fps":30,"processing_fps":2.4,"detection_latency_ms":180,"cpu_percent":78,"memory_mb":1200,"dropped_frames":12 }
```

## Validation Errors (422)

Example: missing `video_asset_id` -> `{ "error": { "code":"VALIDATION_ERROR","message":"video_asset_id is required","details":{"video_asset_id":["required"]} } }`

## Authorization Errors

- 401: missing/invalid service token, `WWW-Authenticate: Bearer`.
- 403: valid token but Laravel role check failed upstream; AI service returns 403 if `X-Role` not allowed for operation.

## Service Unavailable (503)

When model not loaded or queue full: `{ "error": { "code":"SERVICE_UNAVAILABLE","message":"Processing temporarily unavailable, retry after 30s" } }` with `Retry-After: 30`.

## Idempotency / Duplicate Handling

- `Idempotency-Key: <uuid>` header for POST job creation and live start. Server stores key 24h; duplicate key returns original response (201 with same job_id) if same payload, or 422 if payload differs.
- Cancel/stop are naturally idempotent.

## Secret Redaction Rules

- Never log: `Authorization` header value, `AI_SERVICE_TOKEN`, camera password, RTSP URL with credentials, `password`, `token`, `secret`.
- Logs replace with `[REDACTED]`.
- Error responses never echo credential values.
- `GET /api/v1/models` never includes weight file absolute paths; only logical name and checksum.

## Rate Limiting

- Health: 60 req/min per IP.
- Job creation: 10 req/min per service token.
- Exceeded -> 429 with `Retry-After`.

## Proposed Schemas (Pydantic, not implemented in Phase 1)

- `RecordedJobCreateRequest`, `JobStatusResponse`, `LiveStartRequest`, `EventListResponse`, `MetricsResponse`, `ErrorResponse`.

All endpoints are proposed contracts; implementation in Phase 2+ must validate schemas, enforce auth, and add tests before declaring done.
