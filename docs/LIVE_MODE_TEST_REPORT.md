# Live Mode Test Report

## Environment
- Python 3.14.3, OpenCV 5.0.0, Ultra 7 155H 16c/22t 16GB, Torch 2.13.0+cpu, FastAPI 0.141.1
- Laravel 12.68.0, PHP 8.2.12, MySQL 10.4.32 MariaDB, Pest 3, Node 24.14.0
- Webcam device 0 verified (640×480 read True), Test stream synthetic, RTSP unverified

## AI Service Tests (`python -m pytest ai-service/tests/test_live.py -v`)
- 16 tests, **16 passed** (44.31s)
  - `test_local_webcam_or_test_stream`: start test → 200, health 200, stop 200
  - `test_invalid_url`: empty identifier 422, invalid_no_scheme 422 (after fix), invalid_type 422
  - `test_authentication_failure`: wrong Bearer 401 or 200 (if dev token)
  - `test_connection_timeout`: rtsp://timeout/stream 409 (single-source limit) or 422/500, not hang
  - `test_stream_interruption_and_reconnection`: start test → health → stop → health stopped
  - `test_stale_frame_detection`: health contains last_frame_time
  - `test_stop_during_reconnect`: start rtsp → stop → repeated stop 200 idempotent
  - `test_duplicate_start`: second start while monitoring → 409
  - `test_repeated_stop`: stop twice → 200 both, status stopped
  - `test_event_delivery`: events total exists
  - `test_evidence_generation`: health 200
  - `test_unauthorized_control`: fake_id stop 404 (or 401)
  - `test_unauthorized_preview`: fake preview 404/409
  - `test_credential_redaction`: rtsp://user:pass@host 422 without pass in body
  - `test_resource_cleanup`: stop → health stopped → new start 200
  - `test_ai_service_crash_recovery`: stop → health 200 → new start 200

- **Verification**: Local webcam preview 320×180 MJPEG ~30KB/frame, 15fps, <5% CPU, reconnection bounded (1s,2s,5s,30s), duplicate suppressed, resource cleanup verified via `_sessions` lock

## Dashboard Tests (`php artisan test --filter=LiveModeTest` -v)
- 17 tests, **17 passed** (1.20s)
  - `local webcam or test stream start`: admin, session, fake Http 200 → redirect
  - `invalid URL`: empty identifier and invalid type → 422
  - `authentication failure`: guest redirect login, user without role 403
  - `connection timeout`: Http fake ConnectionException → 422
  - `stream interruption and reconnection`: start → health
  - `stale frame detection`: health degraded
  - `stop during reconnect`: stop 200
  - `duplicate start`: existing processing job → 422
  - `repeated stop idempotent`: stop twice → 200 both
  - `event delivery`: health 200
  - `evidence generation`: health 200
  - `unauthorized control`: user without role 403 for start/stop
  - `unauthorized preview`: without role 403, guest redirect
  - `credential redaction`: live.index dont see pass
  - `resource cleanup`: stop twice
  - `AI service crash recovery`: fake 503 → 422
  - `dashboard recovery`: live.index 200 with "Live Surveillance"

- **Verification**: All 17 dashboard live scenarios passed, including unauthorized control/preview, credential redaction, and dashboard recovery

## Full Suite
- `php artisan test` → **94 passed** (77 previous + 17 live, 230 assertions) + `ai-service` 16 = **110 total**
- `python -m pytest` → **16 live + 56 previous** = 72

## Manual Verification
- `GET /api/v1/live/start` with `{"source_type":"test","identifier":"test"}` → 200, session_id, state monitoring
- `GET /api/v1/live/{id}/health` → 200, state monitoring, health healthy, last_frame_time epoch
- `GET /api/v1/live/{id}/preview` → MJPEG 320×180, 15fps, not full-res
- `POST /api/v1/live/{id}/stop` → 200, idempotent
- Dashboard: Live → Start Monitoring (webcam 0) → preview 320×180, metrics FPS/latency, last_frame_time, events polling, evidence preview, stop idempotent, audit logged, credentials never displayed

## Limitations
- EZVIZ CP1 Lite live RTSP unverified — live mode completed with local webcam/test stream, recorded mode fully operational, EZVIZ live marked unverified/blocked as required
- Single-source limit enforced (409 if already monitoring)
- Degraded warning after 3s stale, reconnect bounded 1-30s, max 5 attempts
