# Camera Setup

## Supported Sources (Verified)

### 1. Local Webcam (Verified, Recommended for Phase 7)
- **Device**: `0` (primary webcam, 640×480, 30fps)
- **Verification**: `cv2.VideoCapture(0).isOpened() → True`, read 640×480 True
- **Setup**:
  1. Connect USB webcam or ensure built-in camera enabled
  2. Check Device Manager → Cameras → not disabled
  3. Test: `python -c "import cv2; cap=cv2.VideoCapture(0); print(cap.isOpened()); cap.release()"`
  4. Dashboard: Live → Source `webcam`, Identifier `0`, Session select, Start Monitoring
- **Expected**: Preview 320×180 MJPEG, health `healthy`, FPS ~15, latency ~180ms

### 2. Test Stream (Verified, For CI/No Hardware)
- **Identifier**: `test` or `test_source` (synthetic 320×180 circle moving, 15fps)
- **Setup**: No hardware, select `test` in dashboard, Start Monitoring
- **Expected**: Same pipeline as webcam, events generated via synthetic, no credentials needed

### 3. RTSP (Unverified for EZVIZ CP1 Lite)
- **Status**: Unverified in this environment — see `EZVIZ_CP1_LITE_COMPATIBILITY.md`
- **If verified later**:
  1. Enable RTSP in EZVIZ Studio (requires local IP, not cloud)
  2. Get URL format `rtsp://admin:pass@192.168.x.x:554/stream` (do not publish IP/pass)
  3. Test: `ffmpeg -rtsp_transport tcp -i rtsp://... -t 5 -f null -` or `python -c "import cv2; cap=cv2.VideoCapture('rtsp://...'); print(cap.isOpened())"`
  4. Dashboard: Source `rtsp`, Identifier `rtsp://...` (credentials encrypted, never displayed)
- **Current**: Do not use for EZVIZ live; use webcam/test instead. Recorded download of EZVIZ via SD card works, but live RTSP not verified.

### 4. HTTP Stream (Unverified)
- Similar to RTSP, not verified, same setup via `http` type

## Security
- Credentials (RTSP password, Wi-Fi, account) encrypted via `Crypt::encryptString` in `camera_sources.credentials_encrypted`, hidden in model (`$hidden`), never displayed in views/API
- Identifier stored as `rtsp://host/stream` without password where possible; password in encrypted field only
- Evidence outside public (`storage/app/evidence`), served via authorized controller

## Troubleshooting
- **Webcam not opened**: Check Device Manager, close other apps using camera (Zoom, Teams), try `1` or `2` as identifier
- **RTSP timeout**: Check firewall, `timeout_ms` 5000, reconnection will retry with bounded delay (1s,2s,5s,30s max)
- **Stale frame**: Health `degraded` after 3s no frame, `max_stale_frames` 5

## Recommendation for Phase 7
Use **local webcam (0)** or **test stream** as verified compatible source. Keep EZVIZ recorded mode operational, live unverified until RTSP can be tested in secure lab without exposing credentials.
