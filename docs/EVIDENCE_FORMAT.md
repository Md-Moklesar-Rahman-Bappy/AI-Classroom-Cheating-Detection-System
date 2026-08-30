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

## Example (Phase 3 run)
- 6-frame video, phone every 3rd frame with cooldown 30 -> 1 event -> 1 JPG `evidence/<job_id>/<job_id>_<uuid>.jpg` ~10KB, checksum recorded.
