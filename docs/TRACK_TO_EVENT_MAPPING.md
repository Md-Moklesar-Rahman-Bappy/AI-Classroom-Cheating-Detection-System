# Track-to-Event Mapping

Every event stores exact track association.

## Detection
- D2 Mobile Phone: phone bbox associated to nearest track <300px; event.bbox = track bbox, phone_bbox retained separately.
- D3 Multiple Persons: union bbox, track_id = first track id.

## Behavior
- B1/B2/B3/B5: BehaviorEvent.track_id = observation.track_id, bbox = supporting_geometry bbox, frame_number/timestamp = observation time.
- B3 Looking Backward: MUST use same-frame full-person bbox. B3 event frame = evidence frame, B3 track ID = annotation track ID, B3 event bbox = rendered bbox. No B4 last-known bbox, no nearby-person bbox, no head-only box.
- B4/S3: TwoFrameEvidence - trigger_frame_number/trigger_timestamp (current frame, no bbox) + last_detection_frame_number/last_detection_timestamp/last_detection_bbox (historical frame with bbox). Absence count stored separately. Example last_detected 222 t=7.4s bbox (24,126)-(209,233) trigger 267 t=8.9s absent 45.

## System
- S1 Normal: no track
- S2 Insufficient: per-track uncertain state
- S3 Tracking Lost: as B4 but earlier threshold (S3 >=10 and <45, B4 >=45)

## Evidence Screenshot
Annotator receives event + tracks + render_mode. S3/B4 trigger: metadata only. Last-detected: full-person bbox + Last Known Position. Other codes: same-frame bbox. Invalid boxes rejected; missing last-detected shows Last known position unavailable.

## Verification
Tests assert track_id/bbox, isolation, phone association, two-frame separation, B3 same-frame, invalid rejection, scaling, IDs.
