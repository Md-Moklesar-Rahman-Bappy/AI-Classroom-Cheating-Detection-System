# Tracking Design

## Objective
Anonymous track continuity with temporary session identifiers, no biometric embeddings, no identity persistence.

## Interface
`app/tracking/base.py:Tracker`:
```
update(detections: list[DetectionResult]) -> list[Track]
reset() -> None
Track {track_id, bbox: DetectionResult, age, hits, missing}
```

## Evaluation: ByteTrack vs DeepSORT vs SimpleCentroid

| Criterion | ByteTrack | DeepSORT | SimpleCentroid (baseline) |
|---|---|---|---|
| Installed availability | Not installed (`pip show bytetrack` not found) | Not installed (`deep_sort` not found) | Pure Python, no install |
| CPU requirements | High: Kalman + Hungarian, ~10-30ms/frame | High: Kalman + ReID CNN (appearance) heavy | Very low: centroid distance <1ms |
| License | MIT (ByteTrack) | GPL-3.0 (some DeepSORT impls) - copyleft concern | MIT (custom) |
| Detector compatibility | YOLO person (0) - compatible | Needs ReID model + detector | YOLO person (0) - compatible |
| Track continuity | Strong: handles occlusion via IoU | Strong: appearance helps re-id | Moderate: distance threshold 80px, max_missing 10 |
| Implementation complexity | Medium: requires `supervision`/`bytetrack` package | High: ReID model, embedding storage (privacy risk) | Low: 60 lines |
| Privacy | Stores motion only, but some impls cache embeddings | Stores biometric embeddings - disallowed | No embeddings, only bbox centroid |

**Decision:** Baseline is `SimpleCentroidTracker` (`app/tracking/centroid_tracker.py`).

Rationale:

- No extra dependencies verified (both ByteTrack/DeepSORT not installed, would add scipy/filterpy, increase RAM/R02 risk, GPL risk).
- CPU <1ms preserves `process_every_3` pipeline on Ultra 7 155H 16GB.
- No biometric embeddings stored - complies with "Do not persist biometric embeddings, Do not identify real people".
- Temporary IDs (`next_id` increments, resets per job) - not persisted across jobs, not linked to identity.
- Handles multi-person via per-detection centroid matching within `max_distance` (80px at 640x360).
- Occlusion: `max_missing 10` frames allows brief disappearance; beyond that track deleted, new ID on reappearance (documented limitation).
- Sufficient for MVP temporal rules which need per-track observation buffers; can be swapped for ByteTrack in Phase 8 benchmark without changing `Tracker` interface.

## Configuration (recorded with every job)
`tracking_max_distance` (80.0), `tracking_max_missing` (10) in `settings.py`, stored in `job.output_metadata.behavior_config`.
`config_version` `v1` tracks threshold set.

## Privacy
- Track ID is session-local, not linked to enrollment or face.
- No embedding, no face crop, no ReID vector retained after `update` returns.
- Evidence stores only `track_id` as integer, not identity.

## Limitations
- No appearance re-id: track switch if two persons cross within 80px or one occludes other >10 frames.
- Far-field small bboxes (<20px) still tracked but may have ID jitter.
- Leaving-seat via `max_missing` is proxy, not true seat assignment (see `BEHAVIOR_EVENT_LIMITATIONS.md`).

## Future
Phase 8 may benchmark ByteTrack (MIT) if installed and CPU headroom measured; DeepSORT rejected for GPL and embedding storage.
  
---  
## Appendix: Tracking Failure Analysis (merged from /TRACKING_FAILURE_REPORT.md)  
Archived source: docs\archive\TRACKING_FAILURE_REPORT.md  
  
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
