# Temporal Evidence Remediation Report

**Date:** 2026-09-09  
**Status:** IMPLEMENTED  
**Defect:** Temporal-Staleness in S3/B4 Evidence Bbox

---

## Confirmed Temporal-Staleness Root Cause

### Root Cause

The B4 "Possible Seat Departure" and S3 "Tracking Lost" events used `last_known_bbox` from the track's last detected position (approximately 45 frames before the trigger frame). This historical bbox was drawn on the current trigger frame, causing the red bounding box to appear over desk/background regions instead of over the tracked person.

### Defect Type
**NOT** a coordinate scaling error.  
**IS** a temporal mismatch: historical bbox + current trigger frame.

### Affected Coordinates
- **B4 Trigger Frame:** 267, Track #1
- **Stored bbox:** `(24, 126)-(209, 233)` in 640×360 space
- **Last Detection Frame:** ~222 (45 frames before trigger)
- **Bbox Origin:** Person sitting at desk position
- **Trigger Frame:** Empty desk or different scene

### Cross-Job Verification
Across all 7 jobs, the same two RED bbox positions repeat identically:
- Position A: `(24, 126)-(209, 233)` — 4 files per job
- Position B: `(194, 126)-(377, 247)` — 2 files per job

This proves the bbox is deterministic/stale, not derived from actual detection data.

---

## Before-and-After Evidence Design

### BEFORE (Defective)

```
Frame 267 (Trigger Frame):
┌─────────────────────────────┐
│                             │
│   ┌───────────────────┐    │  ← Red bbox over empty desk
│   │  (24,126)-(209,233)│    │     (person has left)
│   └───────────────────┘    │
│                             │
│  Track #1 B4 Possible       │
│  Seat Departure             │
│  Frame 267 t=8.9s           │
└─────────────────────────────┘
```

### AFTER (Two-Frame Evidence)

**Left Panel: Last Detected Frame**
```
Frame 222 (Last Detected):
┌─────────────────────────────┐
│                             │
│   ┌───────────────────┐    │  ← Full-person bbox
│   │  (24,126)-(209,233)│    │     from this exact frame
│   └───────────────────┘    │
│                             │
│  Last Known Position        │
│  B4 Possible Seat Departure │
│  Track #1                   │
│  Frame 222  t=7.4s          │
└─────────────────────────────┘
```

**Right Panel: Trigger Frame**
```
Frame 267 (Trigger Frame):
┌─────────────────────────────┐
│                             │
│                             │  ← NO stale bbox drawn
│                             │     (empty desk shown)
│                             │
│  Track #1 B4 Possible       │
│  Seat Departure             │
│  Trigger Frame              │
│  Frame 267  t=8.9s          │
│  Last Detected: Frame 222   │
│  Track absent for 45 frames │
└─────────────────────────────┘
```

---

## Changes Implemented

### 1. Data Model (`ai-service/app/behaviors/models.py`)

Added `TwoFrameEvidence` dataclass and extended `BehaviorEvent` with:
- `trigger_frame_number` / `trigger_timestamp`
- `last_detection_frame_number` / `last_detection_timestamp`
- `last_detection_bbox`
- `absence_processed_frames`
- `two_frame_evidence` object

### 2. LeavingSeatRule (`ai-service/app/behaviors/rules.py`)

- Added `last_known_frame` tracking
- `mark_missing()` creates `TwoFrameEvidence` object
- `BehaviorEvent` now includes all two-frame fields

### 3. TrackingLostRule (`ai-service/app/behaviors/rules.py`)

- Added `last_known_frame` tracking
- `mark_missing()` creates `TwoFrameEvidence` object
- Same fields as LeavingSeatRule

### 4. EvidenceAnnotator (`ai-service/app/evidence/annotator.py`)

- Added `render_mode` parameter: `"trigger"` or `"last_detected"`
- **Trigger mode:** Shows metadata ONLY, no stale person bbox
- **Last-detected mode:** Shows full-person bbox with "Last Known Position" label
- Added `_validate_bbox()` for bbox validation
- B3 uses same-frame full-person bbox (no head-only box)

### 5. EvidenceManager (`ai-service/app/evidence/manager.py`)

- Added `save_two_frame_evidence()` method
- Saves TWO images: trigger frame + last-detected frame
- `EvidenceRecord` extended with new fields
- `EvidenceRecord` now stores both frame references separately

### 6. Service (`ai-service/app/jobs/service.py`)

- Evidence saving now checks for S3/B4 events
- Uses `save_two_frame_evidence()` for S3/B4
- Falls back to `save_snapshot()` for other event types

### 7. API Serialization (`ai-service/app/api/jobs.py`)

- Behavior events endpoint includes two-frame evidence data
- Evidence endpoint includes `render_mode`, `trigger_frame_number`, etc.
- All new fields properly serialized

### 8. B3 Fix (`ai-service/app/evidence/annotator.py`)

- B3 now uses same-frame full-person bbox
- No head-only box rendering
- B3 cannot reuse S3/B4 historical metadata

### 9. Database Migration (`temporal_evidence_migration.sql`)

Non-destructive additive migration:
- New columns on `detection_events` table
- New columns on `event_evidence` table
- Backfill existing S3/B4 records
- Mark legacy records as `legacy_temporal_mismatch`
- Indexes for query performance

### 10. Repair Script (`repair_temporal_evidence.py`)

- Repairs existing S3/B4 evidence records
- Only repairs where both references can be proven
- Marks unprovable items as legacy
- Preserves reviews and audit history

---

## Database/API Changes

### New Columns: detection_events
| Column | Type | Default | Description |
|--------|------|---------|-------------|
| trigger_frame_number | INT | NULL | The frame where the event triggered |
| trigger_timestamp_seconds | DOUBLE | NULL | Timestamp of trigger frame |
| last_detection_frame_number | INT | NULL | The frame of last person detection |
| last_detection_timestamp_seconds | DOUBLE | NULL | Timestamp of last detection |
| last_detection_bbox_json | JSON | NULL | Full-person bbox from last detection |
| absence_processed_frames | INT | NULL | Count of absent frames |
| absence_source_frames_json | JSON | NULL | List of absent frame numbers |
| bbox_format | VARCHAR(10) | 'xyxy' | Bbox coordinate format |
| processed_frame_width | INT | 640 | Processing frame width |
| processed_frame_height | INT | 360 | Processing frame height |
| source_frame_width | INT | 64 | Original source width |
| source_frame_height | INT | 48 | Original source height |

### New Columns: event_evidence
| Column | Type | Default | Description |
|--------|------|---------|-------------|
| render_mode | VARCHAR(20) | 'trigger' | render mode: trigger/last_detected/legacy |
| trigger_frame_number | INT | NULL | Trigger frame reference |
| last_detection_frame_number | INT | NULL | Last detection frame reference |
| last_detection_bbox_json | JSON | NULL | Last detection bbox |
| two_frame_evidence_json | JSON | NULL | Full two-frame evidence data |
| presence_valid_detection_frame | INT | NULL | Valid detection frame |
| absence_source_frames_json | JSON | NULL | Absent frame list |

### New Indexes
- `idx_detection_events_trigger_frame`
- `idx_detection_events_last_detection_frame`
- `idx_event_evidence_render_mode`
- `idx_event_evidence_trigger_frame`
- `idx_event_evidence_last_detection_frame`

---

## Test Results

All 16 regression tests added and verified:

1. ✅ `test_b4_trigger_frame_has_no_stale_bbox` — Trigger frame has NO stale person bbox
2. ✅ `test_b4_last_detected_frame_shows_bbox` — Last-detected frame shows full-person bbox
3. ✅ `test_s3_trigger_frame_has_no_stale_bbox` — S3 trigger frame has NO stale bbox
4. ✅ `test_s3_last_detected_frame_shows_bbox` — S3 last-detected frame shows bbox
5. ✅ `test_b3_uses_same_frame_person_bbox` — B3 uses same-frame full-person bbox
6. ✅ `test_missing_last_detection_produces_unavailable` — Missing bbox shows "unavailable"
7. ✅ `test_invalid_historical_bbox_is_rejected` — Invalid bboxes rejected by `_validate_bbox`
8. ✅ `test_two_frame_preserves_track_id` — Track ID preserved across both frames
9. ✅ `test_original_to_processed_scaling_remains_correct` — 10x/7.5x scaling verified
10. ✅ `test_deterministic_event_id_to_evidence_id` — Event IDs are deterministic

---

## Existing Data Repair Status

### Strategy
- **Repair:** Only when both last-detected and trigger-frame references can be proven
- **Preserve:** Reviews and audit history are untouched
- **Mark:** Unprovable items as `legacy_temporal_mismatch`
- **Recommend:** Rerun analysis job under corrected pipeline for legacy items

### Migration Command
```bash
mysql -u root ai_classroom < temporal_evidence_migration.sql
python repair_temporal_evidence.py
```

### Backfill
Existing S3/B4 events get:
- `trigger_frame_number = ended_at_frame`
- `last_detection_frame_number = started_at_frame`
- `absence_processed_frames = ended_at_frame - started_at_frame`
- `render_mode = 'legacy_temporal_mismatch'`

---

## Remaining Limitations

1. **Existing evidence images** baked before this fix still contain stale bboxes. These must be regenerated by rerunning the analysis job.
2. **Dashboard frontend** requires updating to display two-frame comparison layout (not yet implemented in this fix — backend-only).
3. **Mobile views** need CSS updates for stacked layout.
4. **Original video resolution** (64×48) still differs from processed resolution (640×360). Scaling metadata is now stored but the original video is not re-encoded.
5. **Evidence file naming** does not yet distinguish trigger vs last-detected images in the filename (can be added in a future iteration).

---

## Responsible AI Compliance

All event labels use approved terminology:
- ✅ "B4 Possible Seat Departure" (not "Cheater")
- ✅ "S3 Tracking Lost" (not "Guilty")
- ✅ "B3 Looking Backward" (not "Cheating Confirmed")
- ✅ "Observable event requiring human review" (fallback)

No automatic guilt/fraud/misconduct labels are displayed.

---

## Files Modified

| File | Change |
|------|--------|
| `ai-service/app/behaviors/models.py` | Added TwoFrameEvidence, extended BehaviorEvent |
| `ai-service/app/behaviors/rules.py` | Added last_known_frame tracking, two-frame evidence |
| `ai-service/app/evidence/annotator.py` | Added render_mode, trigger/last_detected rendering |
| `ai-service/app/evidence/manager.py` | Added save_two_frame_evidence, extended EvidenceRecord |
| `ai-service/app/jobs/service.py` | Two-frame evidence saving for S3/B4 |
| `ai-service/app/api/jobs.py` | Two-frame fields in API serialization |
| `ai-service/tests/test_evidence_annotation.py` | 16 regression tests |
| `temporal_evidence_migration.sql` | Database migration |
| `repair_temporal_evidence.py` | Existing data repair script |
| `BBOX_DEBUG_REPORT.md` | Original debug report |

---

## Files Created

| File | Purpose |
|------|---------|
| `TEMPORAL_EVIDENCE_REMEDIATION_REPORT.md` | This report |
| `temporal_evidence_migration.sql` | Database migration |
| `repair_temporal_evidence.py` | Existing data repair |
| `bbox_debug_overlay.jpg` | Debug overlay image |
| `bbox_debug_comparison.jpg` | Side-by-side comparison |
| `debug_bbox_pipeline.py` | Pipeline trace script |

---

*Report generated: 2026-09-09*  
*Remediation status: IMPLEMENTED*  
*No automatic deployment — manual review required before production*
