# Evidence Annotation System

## Objective
Reviewer opens screenshot and instantly knows who, what, where, when without reading tables.

## Color Policy (v2 — 11 events)
| Code | Event | Color (BGR) | Meaning |
|------|-------|-------------|---------|
| D1 | Person Detected | (0,200,0) Green | Primary subject |
| D2 | Mobile Phone Detected | (255,0,0) Blue | Phone object |
| D3 | Multiple Persons Detected | (0,255,255) Yellow | Multiple persons |
| B1 | Looking Left | (0,165,255) Orange | Orientation |
| B2 | Looking Right | (0,165,255) Orange | Orientation |
| B3 | Looking Backward | (0,165,255) Orange | Orientation |
| B4 | Possible Seat Departure | (0,0,255) Red | Absence |
| B5 | Excessive Head Movement | (0,165,255) Orange | Instability |
| S3 | Tracking Lost | (128,128,128) Gray | System |
| Other | Non-event students | (160,160,160) Gray 1px | Not highlighted |

Event subject: thick 3px colored box + white 1px outer stroke + filled label plate.

## Single-Subject Rule
If Track #7 triggered event, ONLY Track #7 gets colored thick box + labels. Other tracks get thin gray 1px outline or no annotation. Applies to 2..20+ students.

## Annotation Content
```
Track #4
B1 Looking Left
Frame 42 t=4.2s
```
Rendered as filled color plate with white text above/below bbox. Timestamp and frame number always shown.

## Phone Detection (D2) Association
- Phone bbox centroid compared to all person tracks centroid.
- Nearest track within 300px at resized 640x360 scale wins.
- Event bbox = track bbox (student box), phone bbox retained as `phone_bbox`.
- Annotator draws student thick box + small blue phone box + "Phone" label.
- If no track within distance, event has no track_id, fallback to phone bbox with D2 label.
- Documented in `app/events/rules.py:associate_phone_to_nearest_track`.

## B4 Possible Seat Departure
- Uses last known bbox from `LeavingSeatRule.last_known_bbox`.
- Label is `B4 Possible Seat Departure` — never "Left classroom" or "Cheated".
- Annotator draws last known position with red box + B4 plate + timestamp.

## D3 Multiple Persons
- If >=2 persons detected (optionally inside seat_region) then D3 emitted with union bbox, cooldown 30.
- Annotator draws yellow thick box covering group, label "D3 Multiple Persons Detected" + primary Track #.

## B5 Excessive Head Movement
- Switches left↔right (or backward involvement) counted within window 15; needs both left+right present, switches >=4.
- Label "B5 Excessive Head Movement" orange.

## S3 Tracking Lost
- Absence >=10 and <30 frames; last_known_bbox used, gray box, label "S3 Tracking Lost".

## Responsible AI Labels
Allowed: Person Detected, Mobile Phone Detected, Multiple Persons Detected, Looking Left/Right/Backward, Possible Seat Departure, Excessive Head Movement, Normal, Insufficient Evidence, Tracking Lost.
Forbidden: Cheater, Cheating, Fraud, Misconduct, Violation. Verified in tests.

## Pipeline
```
YOLO -> SimpleCentroidTracker -> GeometricOrientationEstimator -> TemporalEventEngine -> EvidenceAnnotator -> EvidenceManager
```
- Tracker assigns persistent IDs; orientation estimator adds `bbox` to supporting_geometry; engine stores bbox in BehaviorEvent; evidence manager calls EvidenceAnnotator to produce annotated JPG.
- EvidenceRecord stores track_id, event_code, event_label, bbox, checksum.

## API
GET /jobs/{id}/events now returns for each event: event_code, event_label, track_id, frame_number, timestamp_seconds, bbox (+ phone_bbox/associated_track_bbox for D2).

## Evidence Viewer
Detection Events -> Show page: large annotated image, zoom on click (scale 2), Track badge, Frame/time overlay, color policy legend, Full size / Download buttons, metadata.

## Verification
Tests in `tests/test_evidence_annotation.py` cover single frame, multi-student, phone association, B4, repeated left/right/backward, track-to-event bbox correctness, responsible AI, EvidenceManager annotation.
