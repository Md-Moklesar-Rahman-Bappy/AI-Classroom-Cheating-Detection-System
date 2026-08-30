from dataclasses import dataclass


@dataclass
class BehaviorConfig:
    window_size: int = 15
    min_supporting: int = 8
    max_missing: int = 4
    min_duration_frames: int = 10
    cooldown_frames: int = 45
    leaving_absence_frames: int = 30
    seat_region: dict | None = None
    config_version: str = "v1"
