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
