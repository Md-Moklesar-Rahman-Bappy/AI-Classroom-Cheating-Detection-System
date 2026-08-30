# Recorded Video Mode

## Objective
Production-style recorded pipeline that accepts an authorized video, validates it, creates an analysis job, processes frames incrementally, detects person/mobile-phone, draws boxes, generates annotated output, creates de-duplicated events, saves limited evidence, reports progress, supports cancellation/retry, and records actual metrics. Fails safely.

## Pipeline
`Upload -> Validation -> Job(pending->queued) -> Processing -> Annotated Output + Events + Evidence -> Completed/Failed/Cancelled`

Steps per `RecordedAnalysisService.process()`:

1. Validate file exists, extension in `.mp4/.avi/.mov/.mkv`, size <= `max_upload_mb`, `VideoCapture.isOpened()`, dimensions >0, at least one readable frame. Generates safe storage name `uuid+suffix`; stores original filename only as metadata; keeps files under `storage/` outside public directories; prevents traversal (`..`/`/` checks); cleans temporary files in `finally`.
2. `RecordedVideoInput` opens safely, extracts metadata (`width, height, fps, frame_count, codec, duration`), handles unknown frame count (`-1`), preserves source timestamps via `frame_index/fps`, yields `FramePacket` one frame at a time (no full video in RAM), releases `VideoCapture` on success and failure.
3. `FrameScheduler(process_every_n_frames, target_width, target_height)` filters every Nth frame and resizes via `cv2.resize` if needed.
4. `UltralyticsDetector` loaded once (`YOLO(model_path)`), `conf/iou/imgsz` configurable, `allowed_classes=[0,67]` only, returns typed `DetectionResult` (no raw framework objects), checksum via sha256 if weight file exists, never commits weight file.
5. For each scheduled frame: `detector.detect()` with latency measured; `MobilePhoneEventRule(cooldown_frames=30)` filters phone detections with cooldown to prevent one event per frame; `create_events_for_detections()` creates `DetectionEvent(event_type="Mobile Phone Detected", requires_review=True)` preserving detector class/confidence/bbox/frame_number/timestamp.
6. `EvidenceManager` saves selected evidence only (one JPG per event), stores `evidence_id, event_id, job_id, frame_number, timestamp_seconds, image_width/height, file_checksum(sha256), storage_path, created_at, retention_status`; never stores personal names.
7. `BoundingBoxRenderer` draws rectangle + text `class_name confidence` with color (green person, blue phone); never overwrites source; separate file under `outputs/` with `mp4v` codec; checks `writer.isOpened()` else `RuntimeError`; releases writer on every exit path.
8. `InMemoryJobRepository` / `InMemoryEventRepository` hold jobs/events; metrics collected from actual values:
`source_frame_count, processed_frame_count, skipped_frame_count, detection_invocation_count, source_fps, processing_duration_seconds, effective_processing_fps, avg_detection_latency_ms, peak_memory_mb (psutil, if measurable), error_count, event_count`.
9. Progress via `job.progress_percent`, `frames_processed`, `frames_total`; `job_repo.update()` periodically.
10. Cancellation via `cancel_requested` flag; `request_cancel()` transitions `pending/queued->cancelled`, `processing->cancelling->cancelled`; writer/capture released in `finally`.
11. Retry creates new job from failed/cancelled only; rejects invalid transitions.

## Upload Safety
- Allowed MIMEs: `video/mp4, video/avi, video/quicktime, video/x-msvideo, video/x-matroska`; allowed exts `.mp4/.avi/.mov/.mkv`.
- Verify content via `cv2.VideoCapture`; reject unreadable/empty.
- Safe names via `uuid.hex + suffix`; original name stored as metadata only.
- `max_upload_mb` configurable (default 500).
- Files under `storage/` (not executable/public); traversal prevented.
- Temp files from `NamedTemporaryFile` cleaned in `finally`; never executes uploaded content.

## Mobile Phone Event
- Based solely on detector class 67; label "Mobile Phone Detected"; `requires_review=True`; does not claim phone use from object alone.
- Cooldown `event_cooldown_frames` (default 30) de-duplicates; `should_emit()` filters within cooldown window.
- Evidence saved only for emitted events.

## Output Video Metadata
`{path, width, height, fps, processed_frames, checksum}` stored in `job.output_metadata` and verified via sha256.

## CLI
`python -m app.cli --input <video> --output-dir outputs --storage-dir storage --evidence-dir evidence --model-path yolo11n.pt --imgsz 640 --frame-interval 3 --conf 0.25 --iou 0.45 --device cpu --enable-evidence/--disable-evidence --cooldown 30 --json`
Validates all arguments (`frame-interval>=1`, `conf/iou 0-1`, `imgsz>=32`, input exists, no traversal).

## API (Phase 3 implemented)
- `POST /api/v1/jobs/recorded` (multipart file) -> `{job_id, status, progress_percent}`
- `GET /api/v1/jobs/{job_id}` -> `{job_id, status, progress_percent, frames_processed, frames_total, failure_reason, output_metadata}`
- `POST /api/v1/jobs/{job_id}/cancel` -> `{job_id, status}`
- `POST /api/v1/jobs/{job_id}/retry` -> new `{job_id, status}` (only from failed/cancelled else 409)
- `GET /api/v1/jobs/{job_id}/events` -> `{job_id, total, data:[{event_id, event_type, frame_number, timestamp_seconds, class_id, confidence, bbox, requires_review}]}`
- `GET /api/v1/jobs/{job_id}/metrics` -> `{job_id, status, metrics}`

Existing `GET /api/v1/health`, `GET /api/v1/version`, `POST /api/v1/debug/analyze-local` (dev-only, restricted roots) remain.

## Limitations (Phase 3)
- No tracking/ByteTrack, no temporal behavior rules beyond phone cooldown; person detection via YOLO only.
- `InMemory` repositories (no persistence across restarts); suitable for development; SQLite/JSON planned if needed.
- API processes synchronously; no background queue.
- Validated on Python 3.14.3, CPU-only; GPU not used.
