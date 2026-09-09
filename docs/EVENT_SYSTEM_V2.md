# Event System V2 — 11 Events
All 11 codes D1/D2/D3/B1/B2/B3/B4/B5/S1/S2/S3 implemented end-to-end (detector→tracker→orientation→temporal→evidence→API→dashboard→review). See docs/EVENT_TAXONOMY_V2.md compliance matrix.
Categories: detection D1-D3, behavior B1-B5, system S1-S3. Colors: D1 green, D2 blue, D3 yellow, B1-B3/B5 orange, B4 red, S1 green, S2/S3 gray. API exposes event_code/category/label/track_id/frame/timestamp/bbox.

## Two-Frame Evidence (S3/B4 Remediation)
- **Root cause:** S3/B4 events previously used stale `last_known_bbox` (~45 frames old) drawn on the current trigger frame, causing red/gray boxes to appear over empty desk regions.
- **Fix:** Two-frame evidence system stores and renders:
  - **Trigger frame:** Metadata-only annotation (Track #, event label, timestamps, absence count). NO person bbox.
  - **Last-detected frame:** Full-person bbox at last known position with "Last Known Position" label.
- `EvidenceAnnotator.annotate()` accepts `render_mode="trigger"` or `render_mode="last_detected"`.
- `EvidenceManager.save_two_frame_evidence()` saves both images and returns `(trigger_record, last_detected_record)`.
- API serialization includes `two_frame_evidence` object and individual `trigger_frame_number`, `last_detection_frame_number`, `last_detection_bbox`, `absence_processed_frames`.
- Database migration: `temporal_evidence_migration.sql`, repair script: `repair_temporal_evidence.py`.
- B3 uses same-frame full-person bbox (no historical bbox reuse).
- `TwoFrameEvidence` dataclass in `app/behaviors/models.py`.
