# Evidence Format

## Principle
Incident-only, limited, traceable evidence. No personal names. No continuous recording.

## Stored Record (`EvidenceRecord` / filesystem)

```json
{
  "evidence_id": "uuid",
  "event_id": "uuid",
  "job_id": "uuid",
  "frame_number": 12,
  "timestamp_seconds": 1.2,
  "image_width": 640,
  "image_height": 360,
  "file_checksum": "sha256 hex (64 chars)",
  "storage_path": "evidence/{job_id}/{job_id}_{evidence_id}.jpg",
  "created_at": 1724520000.0,
  "retention_status": "active"
}
```

Fields:

- `evidence_id` - safe uuid, not derived from user input
- `event_id` - linked `DetectionEvent.event_id`
- `job_id` - `AnalysisJob.job_id`
- `frame_number` - source frame index where available
- `timestamp_seconds` - `frame_index / fps` where available
- `image_width/height` - dimensions of saved snapshot (after resize)
- `file_checksum` - sha256 of JPG file (integrity)
- `storage_path` - under `evidence/{job_id}/` (outside public/executable dirs); filename `{job_id}_{evidence_id}.jpg` (no user content)
- `created_at` - epoch seconds (ISO 8601 in API)
- `retention_status` - `active | expired | deleted` (Phase 3: only `active`; lifecycle in later phases)

## Event Link
```json
{
  "event_id": "uuid",
  "job_id": "uuid",
  "event_type": "Mobile Phone Detected",
  "frame_number": 12,
  "timestamp_seconds": 1.2,
  "class_id": 67,
  "class_name": "cell phone",
  "confidence": 0.92,
  "bbox": {"x_min": 100, "y_min": 120, "x_max": 180, "y_max": 220},
  "requires_review": true,
  "created_at": 1724520000.0
}
```

- Based on detector output; preserves `class_id, class_name, confidence, bbox`; label "Mobile Phone Detected"; `requires_review=true` (never auto-claims cheating).

## Storage
- Base dir `evidence/` (configurable `evidence_dir`), `base_dir.mkdir(parents=True, exist_ok=True)`.
- Per-job subdirectory `evidence/{job_id}/` (prevents collision, traversal-safe).
- Saved via `cv2.imwrite` JPG; failure returns `None` and increments `error_count` without crashing job; `EvidenceManager.list_for_job(job_id)` returns `*.jpg` list.
- Disabled via `enable_evidence=false` or `--disable-evidence` (no files written).

## Retention & Safety
- Files never executed; MIME is image; not served from public directory.
- Path traversal prevented (`..` checks, `Path` sanitization).
- Original video stored under `storage/` with safe uuid name; evidence references job, not person identity.
- API `GET /jobs/{id}/events` returns evidence metadata, not file content; future signed download routes will enforce auth.

## Phase 4 Extension (Temporal Behavior Events)
```json
{
  "event_id": "uuid",
  "job_id": "uuid",
  "track_id": 3,
  "event_type": "Repeated Looking Left",
  "start_frame": 10,
  "end_frame": 20,
  "start_time": 1.0,
  "end_time": 2.0,
  "observation_count": 11,
  "supporting_observations": 9,
  "missing_observations": 1,
  "config_version": "v1",
  "method_version": "geometric-v1",
  "explanation": "Repeated Looking Left with 11 obs window, min_supporting=8, missing=1",
  "requires_review": true
}
```
- Best representative frame: frame where rule first met (window end, `end_frame`), not every frame. Evidence snapshot saved at `end_frame` via `EvidenceManager.save_snapshot` with same `evidence_id` link, `job_id`, `frame_number`, `timestamp_seconds`, dimensions, checksum. Optional short clip not yet implemented.
- Event stores `track_id` (anonymous), `start/end` times, `config_version` and `method_version` for explainability.
- `GET /jobs/{id}/events` now returns both phone `DetectionEvent` and behavior `BehaviorEvent` merged by `event_type`.

## Example (Phase 3 run)
- 6-frame video, phone every 3rd frame with cooldown 30 -> 1 event -> 1 JPG `evidence/<job_id>/<job_id>_<uuid>.jpg` ~10KB, checksum recorded.

## Example (Phase 4 run)
- 15 frames left with `window 15, min_supporting 8, cooldown 45` -> 1 behavior event at frame 9 -> 1 JPG `evidence/<job_id>/<job_id>_<uuid>.jpg` with behavior `event_id`, plus phone events if any; total evidence limited to events, not frames.

## Phase 5 Evidence Annotation (Annotated Screenshots)
Each `EvidenceRecord` now stores annotated screenshot with single-subject highlight:
```json
{
  "evidence_id": "uuid",
  "event_id": "uuid",
  "job_id": "uuid",
  "frame_number": 12,
  "timestamp_seconds": 1.2,
  "track_id": 4,
  "event_code": "B1",
  "event_label": "Looking Left",
  "bbox": {"x_min": 100, "y_min": 120, "x_max": 180, "y_max": 220},
  "image_width": 640,
  "image_height": 360,
  "file_checksum": "sha256",
  "storage_path": "evidence/{job_id}/{job_id}_{evidence_id}.jpg"
}
```
- Screenshot is annotated via `EvidenceAnnotator`: only event subject gets thick colored box + filled plate `Track #X / Event Label / Frame + Timestamp`; other students gray 1px or omitted.
- Color policy: D1 green, D2 blue, B1/B2/B3 orange, B4 red, S3 gray.
- D2 phone events store `track_id` (nearest student), `associated_track_bbox` (student box used for annotation), `phone_bbox` (phone box), `bbox` = student box if associated else phone box.
- B4/S3 store two-frame evidence: trigger frame (metadata only, no stale bbox) + last-detected frame (full-person bbox). See `docs/EVIDENCE_ANNOTATION_SYSTEM.md` for full spec.

## Phase 5B Two-Frame Evidence (S3/B4 Remediation)
S3/B4 events now store TWO evidence images plus full temporal metadata:
```json
{
  "event_id": "uuid",
  "trigger_frame_number": 267,
  "trigger_timestamp": 8.9,
  "last_detection_frame_number": 222,
  "last_detection_timestamp": 7.4,
  "last_detection_bbox": {"x_min": 24, "y_min": 126, "x_max": 209, "y_max": 233},
  "absence_processed_frames": 45,
  "absence_source_frames": [223, 224, ..., 267],
  "bbox_format": "xyxy",
  "processed_frame_size": {"width": 640, "height": 360},
  "source_frame_size": {"width": 64, "height": 48}
}
```
- **Trigger frame image:** Annotated with metadata text only (Track #, label, timestamps, absence count). No person bbox — proves the person was NOT present at trigger time.
- **Last-detected frame image:** Full-person bbox at last known position with "Last Known Position" label and colored plate.
- `EvidenceManager.save_two_frame_evidence()` returns `(trigger_record, last_detected_record)`.
- File naming: `job_{job_id}_frame_{trigger_frame:06d}_track_{tX}_B4_{evidence_id}.jpg` for trigger and `job_{job_id}_frame_{last_det_frame:06d}_track_{tX}_B4_{evidence_id}.jpg` for last-detected.
- Database migration: `temporal_evidence_migration.sql` adds columns to `detection_events` and `event_evidence` tables.
- Legacy records marked `legacy_temporal_mismatch` via `repair_temporal_evidence.py`.
