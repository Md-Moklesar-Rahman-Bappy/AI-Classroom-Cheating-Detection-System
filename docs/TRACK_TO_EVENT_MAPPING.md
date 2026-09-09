# Track-to-Event Mapping

Every event stores exact track association.

## Detection
- D2 Mobile Phone: phone bbox associated to nearest track <300px; event.bbox = track bbox, phone_bbox retained separately, track_id = nearest track id.
- D3 Multiple Persons: union bbox of all persons, track_id = first track id, track_ids list implied via bbox covering multiple tracks.
- D1 Person: track_id = person track (when used).

## Behavior
- B1/B2/B3/B5: BehaviorEvent.track_id = observation.track_id, bbox = orientation observation's supporting_geometry bbox, frame_number/timestamp = observation time.
- B4/S3: track_id = missing track id. Uses two-frame evidence: `trigger_frame_number` + `last_detection_frame_number`. `bbox` is the trigger-frame metadata bbox (no stale person box). Last-detected frame shows full-person bbox from `last_detection_bbox`.

## Evidence Screenshot
- **Default (D1-D3, B1-B5, S1-S2):** Annotator receives event + tracks list; draws only subject track with color policy (D1 green, D2 blue, D3 yellow, B1/B2/B3/B5 orange, B4 red, S3 gray), other tracks gray 1px. Label plate shows Track #X, Event Code+Name, Frame + timestamp.
- **S3/B4 trigger frame:** Metadata-only annotation (Track #, event label, trigger time, last detected time, absence count). NO person bbox drawn — proves absence at trigger time.
- **S3/B4 last-detected frame:** Full-person bbox at last known position with "Last Known Position" label and colored plate.
- `EvidenceManager.save_two_frame_evidence()` saves both trigger + last-detected images.

## Two-Frame Evidence Fields
| Field | Description |
|---|---|
| `trigger_frame_number` | Frame where event triggered |
| `trigger_timestamp` | Timestamp of trigger frame |
| `last_detection_frame_number` | Frame of last person detection |
| `last_detection_timestamp` | Timestamp of last detection |
| `last_detection_bbox` | Full-person bbox from last detection |
| `absence_processed_frames` | Count of absent frames |
| `absence_source_frames` | List of absent frame numbers |
| `bbox_format` | Coordinate format (xyxy) |
| `processed_frame_size` | {width: 640, height: 360} |
| `source_frame_size` | {width: 64, height: 48} |

## Verification
Tests assert correct track_id/bbox for each event type, multi-student isolation, phone association distance logic documented in app/events/rules.py.
