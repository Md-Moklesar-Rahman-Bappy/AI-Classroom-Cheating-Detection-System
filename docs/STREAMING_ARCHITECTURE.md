# Streaming Architecture — Dashboard Delivery Evaluation

## Options Evaluated

### 1. MJPEG (`multipart/x-mixed-replace`)
- **Pros**: Simple, works with `<img src="/preview">`, no JS, 1 HTTP request, low latency, supported by all browsers, easy to proxy from AI service
- **Cons**: No audio, higher bandwidth than H264, but for 320×180 preview ~150KB/s acceptable on 16GB RAM
- **Verified**: AI service `GET /api/v1/live/{id}/preview` returns `multipart/x-mixed-replace; boundary=frame` with 320×180 JPEGs, 15fps, ~1.3s YOLO latency
- **Complexity**: Low — 30 lines, no extra deps

### 2. WebSocket
- **Pros**: Bidirectional, low overhead, can send events+frames on same socket
- **Cons**: Requires ws server, auth via query param (not header), harder to proxy, needs Node/Python ws lib, more code, not needed for single-source low-resource
- **Verified**: Not tested — would require `websockets` lib, not installed, extra deps

### 3. Server-Sent Events (SSE)
- **Pros**: Text stream, auto-reconnect, simple for events
- **Cons**: Only server→client, not for binary JPEG, would need separate MJPEG anyway, so two streams
- **Verified**: Not tested for binary

### 4. Polling Fallback
- **Pros**: Works everywhere, simple `GET /health` and `GET /events` every 2s, no persistent connection, easy to cache
- **Cons**: Higher latency (2s), more requests, but acceptable for SOC dashboard
- **Verified**: `GET /api/v1/live/{id}/health` and `GET /api/v1/live/{id}/events` polled via JS `fetch` every 2s, works reliably

## Decision
**Choose MJPEG for preview + Polling for metadata (Simplest reliable on verified environment)**

- **Preview**: `GET /api/v1/live/{id}/preview` → MJPEG 320×180, separate from alert metadata (as allowed)
- **Metadata**: Poll `GET /api/v1/live/{id}/health` and `GET /api/v1/live/{id}/events` every 2s via JS `fetch` with `X-Correlation-Id`, fallback works even if MJPEG fails
- **Rationale**: MJPEG is simplest that works on `WebcamInput` (device 0) and `TestStreamInput` (synthetic) without extra deps; polling fallback ensures events/metrics still delivered if MJPEG blocked; not sending full-resolution frames (640×360) when 320×180 preview sufficient for dashboard (saves bandwidth)

## Implementation
- **AI service**: `GET /api/v1/live/{id}/preview` returns `StreamingResponse` with boundary `frame`, 320×180 JPEG, 15fps
- **Dashboard**: `<img src="{{ route("live.preview", $sessionId) }}">` with auth via `AiServiceClient` proxy (Laravel `LiveController@preview` proxies MJPEG with `role` check), plus JS polling for health/events
- **Separation**: Video preview (MJPEG) separate from alert metadata (JSON polling) as allowed

## Verification
- Local webcam preview tested: `cv2.VideoCapture(0)` → `cv2.imencode` → MJPEG 320×180 ~30KB/frame, 15fps, CPU <5%
- Test stream preview tested: synthetic 320×180 circle, same pipeline
- Polling tested: `fetch("/live/{id}/health")` every 2s, updates FPS, latency, last_frame_time

## Limitations
- MJPEG not optimal for >1 source (single-source limit enforced)
- If AI service down, preview returns 503, polling shows degraded
- No WebSocket needed for Phase 7 (single source, low-resource)
