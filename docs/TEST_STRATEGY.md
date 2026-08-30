# Test Strategy

## Python

### Unit Tests
- Config loading: valid YAML, missing file, malformed YAML -> safe defaults or clear error; no hard-coded magic values.
- Detector interface: mock YOLO returns boxes; wrapper translates to internal `Detection` dataclass.
- Model-load failure: missing `yolo11n.pt` raises handled exception, job status `failed` with `failure_reason`, no crash.
- Metrics: collector computes FPS, latency, CPU/mem from psutil; handles zero-division.

### Adapter Tests
- RecordedVideoInput: opens valid mp4, reads frames, handles corrupt file, releases capture on failure.
- WebcamInput / RtspStreamInput / TestVideoInput: same interface; test with dummy video file; verify `is_opened()` false for invalid source.
- Frame scheduler: process-every-N-frames=3 returns every 3rd frame; N=1 returns all; N=5 correct cadence.

### Detector Tests
- Mock detector returns known boxes; assert correct count and class_ids.
- Confidence threshold: boxes below 0.25 filtered; above kept.
- Small object policy: tiny boxes (<16px) handled per annotation guide.

### Video Read/Write Tests
- Read sample mp4 via `cv2.VideoCapture`, verify frame count and fps; write annotated video via `cv2.VideoWriter`, verify file exists and readable.
- Failure: unreadable video file -> `VideoReadError` with message, not silent ignore.

### Temporal-Rule Tests
- Looking left: 5 consecutive left frames -> event B1 generated; 3 left then 2 right -> insufficient (S2).
- Cooldown: after B1 ends, 10 frames suppressed before re-trigger.
- Duplicate suppression: same event spanning overlapping window not duplicated.
- Rule score calculation documented; no fake confidence for deterministic rules.

### Tracking Tests
- ByteTrack/DeepSORT (when introduced): two persons crossing, IDs persist; new person gets new ID; lost track removed after tolerance frames.
- Track continuity for Leaving Seat: movement threshold respected.

### API Tests
- Health: GET /health returns 200 with `model_loaded` bool.
- Auth: request without service token -> 401; with valid token -> 200; invalid token -> 401.
- Validation: POST /jobs/recorded missing video_asset_id -> 422 with details.
- Service unavailable: model not loaded -> 503 with Retry-After.

### Failure Tests
- Stream failure: camera disconnect mid-processing -> status `failed`, resources released, audit logged, user can retry.
- Disk full: evidence write fails -> job `failed` with `failure_reason`, no partial evidence leak.

### Resource Cleanup
- Every test with VideoCapture/VideoWriter asserts `release()` called (mock or `try/finally`).
- No global mutable runtime state; model loaded once per worker.

### Security Tests
- Secret redaction: logs never contain `AI_SERVICE_TOKEN` or RTSP password; test greps logs.
- Path traversal: upload with `../../evil` name -> stored as safe uuid.

## Laravel (when dashboard implemented; verified PHP 8.2.12 compatible Laravel release to be chosen)

### Authentication
- Guest cannot access dashboard; redirects to login; login with valid creds succeeds; invalid creds shows error; audit `login_success`/`login_failure`.

### Authorization
- Invigilator cannot access System Administrator routes (->403); reviewer cannot export reports without permission; evidence access denied across sessions.

### Upload
- Valid mp4 uploads succeeds; .php file -> 422; oversized file -> 422; original_filename stored as metadata only, stored_filename is uuid.

### Jobs
- Create analysis job, view status, cancel, retry; state transitions tested (pending->queued->processing->completed/failed/cancelled).

### Events
- Event list filtered by exam_session; pagination; event detail shows track_id, event_type, review_status.

### Evidence
- Authorized user can view evidence; unauthorized -> 403; evidence served via signed route; audit `evidence_viewed`.

### Reviews
- Reviewer can confirm/dismiss/needs review; note saved; audit `event_reviewed`; duplicate review handled.

### Reports
- Authorized export returns file; unauthorized -> 403; audit `report_exported`.

### Audit Logs
- Every state-changing action creates audit entry with actor, action, target, timestamp, correlation_id; never includes passwords/tokens.

### Service Outage
- AI service down -> Laravel shows friendly error, job status `failed` with retry option; does not expose internal details.

### Rate Limiting
- Login 5/min, upload 10/min; 6th request -> 429.

## Integration

### Recorded-Video End to End
- Upload valid video -> create job -> processing completes -> annotated video exists -> events created -> evidence available -> reviewer records decision -> audit exists -> report exportable.

### Live-Source End to End
- Register webcam source -> test connection (status) -> start monitoring -> stream health shows FPS -> live alert appears -> evidence captured -> reviewer decision -> stop monitoring -> session summary -> audit.

### Failure and Recovery
- Stream disconnection during live -> health shows `failed`, reconnect logic attempts, alert queue paused, user can stop/restart; resources released.
- Invalid model config -> job `failed` with validation error, not crash.

### Unauthorized Access
- Direct URL to evidence from other session -> 403; ID enumeration via uuid not sequential; test with two sessions.

## Research

### Dataset Leakage Checks
- Script verifies no frame from same session_id in both train and test manifests; no adjacent-frame leakage; participant/session-based split respected.

### Per-Class Metrics
- Precision, recall, F1 per class (person, phone, B1-B4); computed on validation/test sets; not invented.

### Confusion Matrix
- Generated per experiment config; exported JSON/CSV.

### FPS, Latency, Resource Usage
- Measured on i5-14500, 8GB RAM, no GPU, at 640x360 and 480x270, process-every-1/3/5; actual numbers recorded; error bars if repeated runs.

### Robustness
- Compare by camera condition (front vs ceiling, bright vs dim, low vs high occlusion) where approved; document.

### Recorded vs Live Comparison
- Same video via RecordedVideoInput vs TestVideoInput; metrics side-by-side.

### Resolution and Frame-Interval Comparison
- Benchmark script exports JSON/CSV with FPS, latency, CPU, memory for each config; reproduction command documented.

## Coverage

- Do not claim 80%, 90%, or any coverage unless measured by configured coverage tool (e.g., `pytest --cov`).
- Tests for every fixed bug; placeholder implementations not called complete; fake tests that assert nothing meaningful are prohibited.
