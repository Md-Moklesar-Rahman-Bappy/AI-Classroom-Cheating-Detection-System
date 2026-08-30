# Orientation Method

## Selected Baseline
**Geometric Head-Orientation Approximation + Rule-Based Body Fallback (`geometric-v1`)** - see `ORIENTATION_METHOD_EVALUATION.md` for selection rationale.

## Implementation
`app/orientation/geometric.py:GeometricOrientationEstimator`

- Input: `Track` (person bbox) + `timestamp`.
- Previous center per `track_id` stored in `_prev_centers` (temporary, per-job, not persisted).
- Compute: `cx, cy, w, h, delta_norm = (cx - prev_cx)/w, aspect = h/w`.
- Thresholds from `settings.py`:
  - `orientation_left_threshold = -0.15`
  - `orientation_right_threshold = 0.15`
  - `orientation_backward_aspect = 1.8`
  - `method_version = "geometric-v1"` recorded per observation and per job.

## States
`forward | left | right | backward | uncertain | unavailable` - no fake probabilities.

- `backward` if `aspect > 1.8` (tall narrow bbox)
- else `left` if `delta_norm < -0.15`
- else `right` if `delta_norm > 0.15`
- else `forward`
- `uncertain` if first observation (`prev is None`) or `abs(delta)<0.02` while nominally left/right
- `unavailable` if `w<=0 or h<=0`

All states have `measurement_quality` (`high/medium/low/uncertain/unavailable`) based on bbox size (`w<20 or h<40 -> low`).

## Observation Output (typed)
`app/orientation/models.py:OrientationObservation`
```
track_id, timestamp, orientation_state, measurement_quality,
supporting_geometry {cx, cy, w, h, delta, aspect},
visible_landmark_count (0 for geometric, None if unavailable),
insufficient_reason (e.g., "first_observation", "insufficient_delta", "invalid_bbox"),
method_version
```

## Privacy
- No face/pose landmarks stored; `visible_landmark_count` always 0 for geometric.
- Geometry is bbox-derived, not biometric.
- Observations are transient per frame, buffered only in `TemporalEventEngine` window (15 frames), not persisted beyond job.

## CPU/Runtime
Measured <1ms/frame (bbox arithmetic) vs 1.29s YOLO detection - negligible overhead. No extra model download.

## Quality Handling
- Small bboxes (`w<20 or h<40`) -> `low` quality, still observed but temporal rules require `high/medium` for event (via buffer missing count).
- First observation per track -> `uncertain` with reason `first_observation` - prevents single-frame events.
- `unavailable` if no person detection for that track.

## Limitations
- Coarse: cannot distinguish subtle head turn within 15% width delta; will be `uncertain`.
- Backward detection via aspect is proxy, not true head yaw; tall narrow bbox may be person standing, not looking back (documented).
- No true landmark count; `0` indicates geometric method.

## Future
MediaPipe Tasks Pose (`pose_landmarker.task`) and YOLO-pose (`yolo11n-pose.pt`) remain candidates for Phase 8 if FPS headroom measured and landmark stability verified at classroom distance.
