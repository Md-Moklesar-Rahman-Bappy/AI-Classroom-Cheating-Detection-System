from dataclasses import dataclass


@dataclass
class BehaviorConfig:
    window_size: int = 15
    min_supporting: int = 8
    max_missing: int = 4
    min_duration_frames: int = 10
    cooldown_frames: int = 45
    leaving_absence_frames: int = 45
    seat_region: dict | None = None
    config_version: str = "v2.1-accuracy"
    multiple_persons_threshold: int = 2
    multiple_persons_iou_threshold: float = 0.3
    head_movement_switch_threshold: int = 4
    head_movement_window: int = 15
    tracking_lost_frames: int = 15
    tracking_lost_cooldown: int = 30
