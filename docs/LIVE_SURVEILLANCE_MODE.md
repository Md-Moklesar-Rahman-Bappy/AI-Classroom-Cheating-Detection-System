# Live Surveillance Mode

## Overview
Live mode provides secure, low-resource, single-source monitoring using the **same shared engine** as recorded mode (YOLO11n detector, SimpleCentroidTracker, GeometricOrientationEstimator, TemporalEventEngine, BoundingBoxRenderer, EvidenceManager, MetricsCollector). No duplication.

## Source Abstraction
- **CameraSourceConfig** (`app/inputs/camera_config.py`): `source_type` (webcam|rtsp|http|test), `identifier` (device index 0 or RTSP URL), `width` 640, `height` 360, `fps` 15, `timeout_ms` 5000, `reconnect_max_attempts` 5, `reconnect_base_delay_ms` 1000, `reconnect_max_delay_ms` 30000, `frame_timeout_ms` 3000, `max_stale_frames` 5
- **Inputs**: `WebcamInput` (device 0, verified), `RtspStreamInput` (URL with validation, redacted logging), `TestStreamInput`/`TestVideoInput` (synthetic 320×180 circle, for CI)
- Each implements `InputSource`: `open()`, `metadata()`, `frames() -> Iterator[FramePacket]`, `close()` with guaranteed `release()` in `finally`

## Source States
`unconfigured → testing → connected → monitoring → reconnecting → degraded → disconnected → stopped → failed`
- `testing`: validating identifier
- `connected`: `cap.isOpened()` true
- `monitoring`: `frames()` yielding, health `healthy`
- `reconnecting`: on `read()` failure or `frame_timeout`, bounded delay `min(base*2^(n-1), max)`
- `degraded`: `stale_count` >=1 but < max, health `degraded`
- `disconnected`: after max retries
- `stopped`: `stop_token` set, thread joined, queue cleared
- `failed`: after max reconnect attempts

## Health
- `HealthState`: `healthy` (frame within timeout), `degraded` (1 stale), `unhealthy` (reconnecting), `unknown` (stopped)
- `last_frame_timestamp` updated per frame via `time.time()`
- `frame_timeout` 3s, `max_stale_frames` 5 → degraded after 3s, reconnect after 15s

## Reconnection Policy
- Bounded delay: `delay = min(1000*2^(attempt-1), 30000)ms`, max 5 attempts
- Example: 1s, 2s, 4s, 8s, 16s (max 30s)
- Single-source limit via `threading.Semaphore(1)` — second `POST /live/start` while monitoring returns `409 Conflict`

## Live Processing (Shared Engine)
```
LiveSession -> _get_input_source(config) -> source.open()
  -> for packet in source.frames():
       if stop_token: break
       if stale: health=degraded, state=reconnecting
       dets = detector.detect(frame)  # YOLO11n, same as recorded
       tracks = tracker.update(dets)  # SimpleCentroid, same
       observations = [orientation.estimate(tr, ts) for tr in tracks]  # geometric-v1
       for obs: engine.mark_seen(), engine.process_observation() -> BehaviorEvents (B1/B2/B3/B4)
       for missing: engine.mark_missing_tracks() -> LeavingSeat (B4 proxy)
       active_events = [e for e in events if e.end_frame==frame_index]
       annotated = renderer.render(frame, dets, tracks, observations, active_events)
       small = cv2.resize(annotated, (320,180)); jpeg = cv2.imencode(".jpg", small)
       preview_queue.put_nowait(jpeg)  # drop oldest if full
       metrics.fps = frame_count / elapsed, latency_ms, last_frame_time
```

- **No duplication**: detector, tracker, orientation, temporal rules, renderer, evidence, metrics are same instances as recorded
- **Evidence**: `EvidenceManager.save_snapshot` per `BehaviorEvent` (incident, not every frame), best representative frame, `file_path` outside public, `checksum`

## Start/Stop
- `POST /api/v1/live/start` with `source_type`, `identifier`, `session_name` → validates, checks single-source limit, creates `LiveSession` (uuid), spawns daemon thread `_run_live`, state `monitoring`, audit `live_start`, returns `session_id`
- `POST /api/v1/live/{id}/stop` → idempotent: if already `stopped` returns 200, else sets `stop_token`, joins thread 5s, clears queue, state `stopped`, audit `live_stop`, releases `VideoCapture`
- **Graceful shutdown**: `stop_token` checked each frame, `finally: source.close()`, `preview_queue` cleared, `Semaphore` released
- **Authorization**: `require_auth` (Bearer token if not dev), `X-Correlation-Id` header, structured error mapping, secret redaction

## Metrics (Live)
- `fps` (frame_count / elapsed), `latency_ms` (detector time), `last_frame_time` (epoch), `frame_count`, `dropped_frames` (queue full), `reconnect_count`, `alert_latency_ms` (event time - detect time)

## Evidence (Live)
- Per `BehaviorEvent` (B1-B4) and `D2` phone, `EvidenceManager` saves 320×180 annotated snapshot, `evidence/{session_id}/{event_id}.jpg`, `checksum_sha256`

## Annotated Preview
- 320×180 JPEG, not full-res 640×360, saves bandwidth, separate from alert metadata (JSON polling)

## Verification
- Local webcam (device 0) preview: `cv2.VideoCapture(0)` → 640×480 read True, 320×180 preview ~30KB, 15fps, <5% CPU
- Test stream preview: synthetic circle, same pipeline, verified in `test_local_webcam_or_test_stream`
- RTSP unverified for EZVIZ (see compatibility report) — live mode uses webcam/test, recorded remains fully operational

## Limitations
- Single-source low-resource limit: 1 active live session at a time (409 if already monitoring)
- Degraded/offline warning via health `degraded`/`unhealthy` and UI badge
- Stop releases resources even during reconnect (tested)
