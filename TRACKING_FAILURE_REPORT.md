# TRACKING FAILURE REPORT — S3 Tracking Lost / B4 Possible Seat Departure

## Executive Summary
S3 (Tracking Lost) and B4 (Possible Seat Departure) are emitted when a centroid track disappears for N consecutive frames, even though the person remains visible. Root cause is centroid-tracker brittleness, not student movement.

## Tracker Audited
`ai-service/app/tracking/centroid_tracker.py:11` — `SimpleCentroidTracker(max_distance=80.0, max_missing=10)` `dashboard/.env` + `ai-service/app/config/settings.py:29-30` track param.

### Algorithm (lines 22-66)
```
person_dets = [d for d in detections if class_id==0]
for det: centroid = ((x_min+x_max)/2,(y_min+y_max)/2)
  best_id = min dist to unmatched track centroid where dist < max_distance(80)
  if best_id: update track bbox, hits++, missing=0
  else: unmatched_dets -> new track
for unmatched_tracks: missing++, age++; if missing > max_missing(10): DELETE track
```

### Failure Modes Verified in Source

| Mode | Location | Effect |
|---|---|---|
| `max_missing=10` deletes track after 10 missed frames (~1.0s at 10fps processing) | `centroid_tracker.py:58-59` | Occlusion / detector miss for 11 frames = ID deleted |
| `max_distance=80px` in 640x360 frame | `centroid_tracker.py:41` | Fast motion / camera shake >80px = new ID, old ID becomes "missing" |
| No occlusion handling | whole file | Overlapping students => YOLO NMS may merge boxes, one track gets no detection => missing++ |
| No Re-ID / appearance feature | whole file | Only centroid distance; identical clothing / low res 640x360 cannot disambiguate |
| `process_every_n_frames=3` skips 2/3 frames | `service.py:259` | Temporal gaps enlarge effective missing count |

## Lost-Track Logic Audited

### S3 Tracking Lost
`ai-service/app/behaviors/rules.py:255-305` `TrackingLostRule.mark_missing()`
```python
absence = frame - last_seen[track_id]          # frames since last seen
if tracking_lost_frames(10) <= absence < leaving_absence_frames(30) and cooldown(30) elapsed:
    emit S3
```
### B4 Possible Seat Departure
`ai-service/app/behaviors/rules.py:199-249` `LeavingSeatRule.mark_missing()`
```python
absence = frame - last_seen[track_id]
if absence >= leaving_absence_frames(30) and cooldown(45) elapsed:
    emit B4 label="Possible Seat Departure" bbox=last_known_bbox
```
Both check `ai-service/app/behaviors/engine.py:52-68` `mark_missing_tracks()` which iterates `temporal_engine.leaving_rule.last_seen` vs `current_tids`.

**Sequence that produces false S3/B4 while visible:**
1. Frame N: person detected, track T active, `mark_seen(T,N,bbox)`
2. Frames N+1..N+11: YOLO misses person (motion blur / occlusion / confidence 0.24 <0.25) => tracker missing++ => after 11th miss track deleted in tracker but `last_seen` still N
3. Frame N+11: `absence=11` => S3 emitted (10<=11<30) with `bbox=last_known_bbox` at N
4. Frame N+31: `absence=31` => B4 emitted with same stale bbox

Visual: student never moved; bbox is stale last-known box from N.

## Evidence For Every S3/B4 Should Show

| Field | Source | What reviewer sees |
|---|---|---|
| frame | `packet.frame_index` `service.py:330,354` | Frame where absence threshold crossed |
| track | `track_id` | ID that went missing |
| bbox | `last_known_bbox` `rules.py:204,260` | Stale box from last-seen frame, not current |
| reason | `explanation` field | `f"Tracking lost: absence {absence} frames >= {tracking_lost_frames}"` / `f"Prolonged absence {absence} frames >= {leaving_absence_frames} (MVP proxy: track missing)"` |

Current explanation strings are in source (`rules.py:247,303`) but not surfaced in dashboard evidence detail view — reviewer cannot distinguish real departure vs tracker failure.

## Mitigations Applied / Recommended

| Fix | File | Priority |
|---|---|---|
| Annotator now shows `S3 Tracking Lost` gray (128,128,128) and `B4 Possible Seat Departure` red (0,0,255) with stale bbox clearly marked | `evidence/annotator.py:11,15` | Done |
| Dashboard must display `absence` count + `explanation` + "stale bbox — human review required" | dashboard detection-events show | HIGH |
| Increase `tracking_max_missing` from 10 -> 15-20 or make per-environment | `settings.py:30` `service.py:206-207` | Medium |
| Increase `max_distance` 80 -> 100-120 for 640x360 or adaptive to bbox size | same | Medium |
| Add required review statement on S3/B4 pages | thesis compliance | Critical |

## Verdict
S3/B4 false positives are **architectural** — centroid tracker is MVP proxy, documented in `docs/BEHAVIOR_EVENT_LIMITATIONS.md`. Not a student behavior, but a CV limitation. Every S3/B4 requires human review; no auto-accusation.

Generated: TRACKING_FAILURE_REPORT.md
