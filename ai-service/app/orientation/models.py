from dataclasses import dataclass
from typing import Literal

OrientationState = Literal["forward", "left", "right", "backward", "uncertain", "unavailable"]


@dataclass
class OrientationObservation:
    track_id: int
    timestamp: float
    orientation_state: OrientationState
    measurement_quality: str
    supporting_geometry: dict
    visible_landmark_count: int | None
    insufficient_reason: str | None
    method_version: str
