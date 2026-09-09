# Event System V2 — 11 Events
All 11 codes D1/D2/D3/B1/B2/B3/B4/B5/S1/S2/S3 implemented end-to-end. Categories: detection D1-D3, behavior B1-B5, system S1-S3. Colors: D1 green, D2 blue, D3 yellow, B1-B3/B5 orange, B4 red, S1 green, S2/S3 gray. API exposes event_code/category/label/track_id/frame/timestamp/bbox.

## Temporal Fix (2026-09-09)
- S3/B4 emit TwoFrameEvidence: trigger_frame_number/trigger_timestamp + last_detection_frame_number/last_detection_timestamp + last_detection_bbox + absence_processed_frames.
- Evidence: two images per S3/B4 (trigger metadata-only, last-detected with bbox). Single-image fallback uses last-detected frame.
- B3 audited: same-frame full-person bbox only. No reuse of S3/B4 historical metadata.
- API GET /jobs/{id}/events includes two_frame_evidence; GET /jobs/{id}/evidence includes render_mode.
