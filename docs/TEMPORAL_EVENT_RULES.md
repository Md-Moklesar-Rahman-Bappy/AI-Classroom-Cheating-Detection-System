# Temporal Event Rules

## Configuration (not hard-coded)
`app/behaviors/config.py:BehaviorConfig`
```
window_size = 15
min_supporting = 8
max_missing = 4
min_duration_frames = 10
cooldown_frames = 45
leaving_absence_frames = 30
config_version = "v1"
```
All thresholds in `settings.py` (`behavior_*`) and recorded per job in `job.output_metadata.behavior_config` and `job.metrics.config_version`.

## Engine
`app/behaviors/engine.py:TemporalEventEngine`
- Per-track observation buffer (max `window_size`).
- `process_observation(obs, frame, job_id)` -> list[BehaviorEvent]
- `mark_seen(track_id, frame)` / `mark_missing_tracks(missing_ids, frame, job_id)` for leaving-seat.
- `is_insufficient(obs)` checks `uncertain/unavailable` or `low` quality.

### General Logic
- Buffer appended each frame, pruned to `window_size`.
- Count `supporting` (state == target) and `missing` (uncertain/unavailable).
- If `supporting >= min_supporting` and `missing <= max_missing` and `len(buffer) >= min_duration_frames` and `supporting/len(buffer) >= ratio` (0.5 for left/right, 0.3 for backward) then emit.
- Cooldown: `frame - last_event_frame[track_id] < cooldown_frames` -> suppress duplicate.
- Track-loss: if `missing > max_missing`, no event (insufficient).
- Uncertain: not counted as supporting; contributes to `missing`.

## Rules

### 1. RepeatedLookingLeftRule
- Target `left`.
- Ratio `0.5`.
- Explanation: `"Repeated Looking Left with {len} obs window, min_supporting=8, missing={missing}"`.
- Brief left (2 frames) -> no event because `supporting 2 <8`.

### 2. RepeatedLookingRightRule
- Same as left but `right`.
- Separate buffer per track, same thresholds.

### 3. LookingBackwardRule
- Target `backward`.
- `min_supporting = max(3, min_supporting//2) =4`, ratio `0.3` (less strict, backward rarer).
- Emits `Looking Backward`.

### 4. LeavingSeatRule
- Not based on camera view exit.
- MVP proxy: **Prolonged absence from established track region** (`mark_missing`).
- Tracks `last_seen[track_id]`; if `frame - last_seen >= leaving_absence_frames (30)` then emit `Leaving Seat` with explanation `"Prolonged absence {absence} frames >=30 (MVP proxy: track missing)"`.
- Limitations documented in `BEHAVIOR_EVENT_LIMITATIONS.md`: not true seat assignment; alternative is manually configured seat region (not implemented in Phase 4, marked partially implemented).
- Cooldown same as other rules.
- If reliable seat region via manual ROI not implemented, event is based on track missing, not seat map.

### 5. InsufficientEvidenceRule
- If `obs.orientation_state in (uncertain, unavailable)` or `measurement_quality in (low, unavailable)` then `is_insufficient == True`.
- No event emitted; observation counted as `missing` in other rules.
- Visualization shows gray, not red.

## Event Output (explainable)
`app/behaviors/models.py:BehaviorEvent`
```
event_id (uuid), job_id, track_id,
event_type ("Repeated Looking Left", "Repeated Looking Right", "Looking Backward", "Leaving Seat"),
start_frame, end_frame, start_time, end_time,
observation_count, supporting_observations, missing_observations,
config_version, method_version,
explanation (human-readable with thresholds),
requires_review = True
```
Every event has `explanation` with thresholds and counts for audit.

## Event End, Cooldown, Duplicate Suppression
- Event end = current frame when buffer criteria first met (window sliding).
- Cooldown 45 frames (~4.5s at 10fps) suppresses same track/type; next event only after gap.
- Duplicate suppression per `track_id` + `event_type` via `last_event_frame`.

## Track-Loss Behavior
- If track missing > `max_missing`, buffer accumulates `uncertain` -> no event.
- If track deleted (`max_missing 10` in tracker) and reappears, new `track_id` -> new buffer (no carry-over).

## Uncertain Behavior
- First observation per track is `uncertain` -> counted as missing, prevents immediate event.
- Low-quality bboxes still buffered but not supporting.

## Visualization Mapping
- Green: `forward` normal
- Amber: accumulating (`left/right/backward` without yet meeting `min_supporting`) or `uncertain`
- Red: temporal event active (track_id in `active_event_tids`)
- Blue: phone (separate)
- Gray: `insufficient` (`unavailable/uncertain` quality low)

Renderer displays `ID:{tid} {state} q:{quality} {event_type}`.

## Example
15 frames left with `min_supporting 8, window 15, duration 10, cooldown 45`:
- Frames 0-9: supporting 10, missing 0, len 10 => emit at frame 9 (first), explanation includes thresholds.
- Frames 10-13: suppressed (cooldown).
- Frame 14+ after cooldown expiry: next emit possible.
