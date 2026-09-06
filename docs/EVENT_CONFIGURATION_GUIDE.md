# Event Configuration Guide (v2)

All thresholds in `app/config/settings.py` and `app/behaviors/config.py:BehaviorConfig` (version v2). No hard-coded values outside config.

| Parameter | Env/Settings Key | Default | Description |
|-----------|------------------|---------|-------------|
| window_size | behavior_window_size | 15 | observation buffer size |
| min_supporting | behavior_min_supporting | 8 | min left/right support for B1/B2 |
| max_missing | behavior_max_missing | 4 | max uncertain count |
| min_duration_frames | behavior_min_duration | 10 | min buffer length |
| cooldown_frames | behavior_cooldown_frames | 45 | duplicate suppression |
| leaving_absence_frames | behavior_leaving_absence | 30 | B4 trigger |
| tracking_lost_frames | tracking_lost_frames | 10 | S3 trigger |
| tracking_lost_cooldown | tracking_lost_cooldown | 30 | S3 cooldown |
| head_movement_switch_threshold | head_movement_switch_threshold | 4 | B5 switches needed |
| head_movement_window | head_movement_window | 15 | B5 window |
| multiple_persons_threshold | multiple_persons_threshold | 2 | D3 person count |
| multiple_persons_iou_threshold | multiple_persons_iou_threshold | 0.3 | D3 overlap |
| tracking_max_distance | tracking_max_distance | 80 | centroid matcher |
| tracking_max_missing | tracking_max_missing | 10 | tracker delete |
| orientation_left_threshold | orientation_left_threshold | -0.15 | geometric |
| orientation_right_threshold | orientation_right_threshold | 0.15 | geometric |
| orientation_backward_aspect | orientation_backward_aspect | 1.8 | aspect ratio |

Tune via `.env` without code change. Job output_metadata records effective config per job.

Example `.env`:
```
BEHAVIOR_WINDOW_SIZE=15
BEHAVIOR_MIN_SUPPORTING=8
MULTIPLE_PERSONS_THRESHOLD=2
HEAD_MOVEMENT_SWITCH_THRESHOLD=4
TRACKING_LOST_FRAMES=10
```
