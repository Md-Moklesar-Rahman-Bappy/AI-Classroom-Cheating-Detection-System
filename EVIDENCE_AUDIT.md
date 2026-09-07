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
