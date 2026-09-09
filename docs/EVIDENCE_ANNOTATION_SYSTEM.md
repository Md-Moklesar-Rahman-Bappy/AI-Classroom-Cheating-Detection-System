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
- Uses two-frame evidence: `trigger_frame_number` + `last_detection_frame_number`.
- **Trigger frame rendering:** Shows metadata only (Track #, event label, trigger frame/time, last detected frame/time, absence count). NO stale person bbox drawn.
- **Last-detected frame rendering:** Shows full-person bbox at last known position with "Last Known Position" label.
- Label is `B4 Possible Seat Departure` — never "Left classroom" or "Cheated".
- `TwoFrameEvidence` dataclass stores `trigger_frame_number`, `trigger_timestamp`, `last_detection_frame_number`, `last_detection_timestamp`, `last_detection_bbox`, `absence_processed_frames`, `absence_source_frames`, `bbox_format`, `processed_frame_size`, `source_frame_size`.

## S3 Tracking Lost
- Two-frame evidence like B4. Absence >=10 frames; trigger frame shows metadata only (no stale bbox). Last-detected frame shows gray full-person bbox.
- Label `S3 Tracking Lost` — gray box on last-detected frame, metadata-only on trigger frame.

## Two-Frame Evidence System (S3/B4)
- Root cause fix: Previously, S3/B4 events drew a stale `last_known_bbox` from ~45 frames ago onto the current trigger frame, making the red/gray box appear over empty desk regions.
- Now `EvidenceAnnotator.annotate()` accepts `render_mode="trigger"` or `render_mode="last_detected"`.
  - `trigger`: Metadata text only (Track #, label, timestamps, absence count). No person bbox.
  - `last_detected`: Full-person bbox with "Last Known Position" label.
- `EvidenceManager.save_two_frame_evidence()` saves TWO images per S3/B4 event: trigger frame + last-detected frame.
- API returns `two_frame_evidence` object and individual `trigger_frame_number`, `last_detection_frame_number`, etc.

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
  
---  
## Appendix: Evidence Audit Summary (merged from /EVIDENCE_AUDIT.md)  
Archived source: docs\archive\EVIDENCE_AUDIT.md  
  
# EVIDENCE AUDIT — Screenshot Generation & Persistence

## Finding: Are screenshots at event frame or latest frame?

**Answer: At event frame** — verified in source.

`ai-service/app/jobs/service.py:326-337` behavior events:
```python
rec = evidence_manager.save_snapshot(frame_proc, job_id, ev.event_id, packet.frame_index, packet.timestamp_seconds, event_obj=ev, tracks=tracks, detections=dets)
```
`service.py:380-391` D2 phone events, `408-420` D3 events, `353-365` leaving/S3 events — all pass `packet.frame_index` and `frame_proc` (the **current** frame being processed).

`ai-service/app/evidence/manager.py:43-110` `save_snapshot()` annotates that exact `frame` via `EvidenceAnnotator.annotate(frame, event_obj, tracks=tracks)` then `cv2.imwrite(str(storage_path), frame)`.

So `event_evidence.frame_number` == `packet.frame_index` == annotated screenshot frame. Not latest frame.

## Why Different Events Look Identical Then?

1. **Same frame, multiple events** — `MobilePhoneEventRule` + `MultiplePersonsRule` + behavior engines can all fire on same `packet.frame_index`. Each gets its own evidence file but base image is same `frame_proc` (resized 640x360 `scheduler.preprocess`). Only overlay differs (color + label). At thumbnail size difference is subtle.

2. **S3/B4 stale bbox** — `LeavingSeatRule`/`TrackingLostRule` use `last_known_bbox` from `last_seen` frame (stale) but screenshot is **current** frame (person still visible elsewhere). So annotated box appears offset from current person position — looks identical to other events if not inspected.

3. **Resolution** — `settings.input_width=640 input_height=360` low res; 1px gray other-student boxes vs 3px colored triggering box is hard to differentiate in gallery thumbnails.

## BBox/Timestamp Persistence

| Field | Persisted Where | Source |
|---|---|---|
| `frame_number` | `event_evidence.frame_number` (int) | `manager.py:93-98` |
| `captured_at_seconds` | `event_evidence.captured_at_seconds` (float) | same |
| `bbox` | `event_obj.bbox` dict `{x_min,y_min,x_max,y_max}` stored in `EvidenceRecord.bbox` and as image overlay | `manager.py:107` |
| `timestamp_seconds` | `EvidenceRecord.timestamp_seconds` | same |
| `checksum_sha256` | SHA256 of jpg file | `manager.py:91` |

Verified: `manager.py:107` `bbox=dict(bbox) if isinstance(bbox, dict) else None` persists box; `annotator.py:75-77` chooses `event.bbox or event.associated_track_bbox`.

## D2 vs B4 vs S3 Verification

| Code | BBox Used | Screenshot Frame | Annotated? |
|---|---|---|---|
| D2 | `event.bbox` = phone bbox + `associated_track_bbox` = person bbox; annotator draws person box color blue + extra phone box `Phone` label `annotator.py:160-177` | event frame | Yes — blue person box + blue phone mini-box |
| B4 | `last_known_bbox` (stale) red (0,0,255) `annotator.py:11` | event frame where absence>=30 (current frame, box stale) | Yes — red box at last-seen position |
| S3 | same `last_known_bbox` gray (128,128,128) `annotator.py:15` | event frame where 10<=absence<30 | Yes — gray box |

## Before/After Comparison

**Before fix (suspected):** No annotation of other students; all students same color; reviewer could not tell which track triggered.

**After (`annotator.py:88-97`):**
```python
for tr in tracks:
  if tr.track_id == track_id: continue
  cv2.rectangle(out, (x1,y1,x2,y2), OTHER_STUDENT_COLOR(160,160,160), 1) # gray
...
cv2.rectangle(out, (x1,y1,x2,y2), color, 3) # triggering track colored + white border
cv2.rectangle(out, (x1-1,y1-1,x2+1,y2+1),(255,255,255),1)
```
Plus 3-line label: `Track #X`, `Event Code + Name`, `Frame N t=s` with colored background `annotator.py:131-158`.

Requirement met: **Only triggering track highlighted with event color (D1 green, D2 blue, D3 yellow, B1/B2/B3/B5 orange, B4 red, S3 gray); others gray 1px.**

## Remaining Issue: Thumbnail Confusability
Gallery thumbnails (12 per page `EvidenceController.php:28`) at small size obscure the 1px vs 3px difference. Fix: gallery should show colored badge + Track ID text overlay, not rely solely on box thickness.

Generated: EVIDENCE_AUDIT.md
