# Track-to-Event Mapping

Every event stores exact track association.

## Detection
- D2 Mobile Phone: phone bbox associated to nearest track <300px; event.bbox = track bbox, phone_bbox retained separately, track_id = nearest track id.
- D3 Multiple Persons: union bbox of all persons, track_id = first track id, track_ids list implied via bbox covering multiple tracks.
- D1 Person: track_id = person track (when used).

## Behavior
- B1/B2/B3/B5: BehaviorEvent.track_id = observation.track_id, bbox = orientation observation's supporting_geometry bbox, frame_number/timestamp = observation time.
- B4/S3: track_id = missing track id, bbox = last_known_bbox before disappearance, timestamp = current frame time.

## System
- S1 Normal: no track (global state)
- S2 Insufficient: per-track uncertain state, not emitted as event but visualized gray
- S3 Tracking Lost: as B4 but earlier threshold

## Evidence Screenshot
Annotator receives event + tracks list; draws only subject track with color policy (D1 green, D2 blue, D3 yellow, B1/B2/B3/B5 orange, B4 red, S3 gray), other tracks gray 1px. Label plate shows Track #X, Event Code+Name, Frame + timestamp.

## Verification
Tests assert correct track_id/bbox for each event type, multi-student isolation, phone association distance logic documented in app/events/rules.py.
