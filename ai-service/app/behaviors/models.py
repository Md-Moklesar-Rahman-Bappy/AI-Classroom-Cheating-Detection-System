from dataclasses import dataclass


BEHAVIOR_CODE_MAP = {
    "Repeated Looking Left": "B1",
    "Repeated Looking Right": "B2",
    "Looking Backward": "B3",
    "Leaving Seat": "B4",
    "Excessive Head Movement": "B5",
    "Tracking Lost": "S3",
    "Insufficient Evidence": "S2",
    "Normal": "S1",
}

BEHAVIOR_LABEL_MAP = {
    "B1": "Looking Left",
    "B2": "Looking Right",
    "B3": "Looking Backward",
    "B4": "Possible Seat Departure",
    "B5": "Excessive Head Movement",
    "S1": "Normal",
    "S2": "Insufficient Evidence",
    "S3": "Tracking Lost",
    "Repeated Looking Left": "Looking Left",
    "Repeated Looking Right": "Looking Right",
    "Looking Backward": "Looking Backward",
    "Leaving Seat": "Possible Seat Departure",
    "Excessive Head Movement": "Excessive Head Movement",
    "Tracking Lost": "Tracking Lost",
}

BEHAVIOR_CATEGORY_MAP = {
    "B1": "behavior",
    "B2": "behavior",
    "B3": "behavior",
    "B4": "behavior",
    "B5": "behavior",
    "S1": "system",
    "S2": "system",
    "S3": "system",
}


@dataclass
class BehaviorEvent:
    event_id: str
    job_id: str
    track_id: int
    event_type: str
    event_code: str
    event_label: str
    start_frame: int
    end_frame: int
    start_time: float
    end_time: float
    frame_number: int
    timestamp_seconds: float
    bbox: dict | None
    observation_count: int
    supporting_observations: int
    missing_observations: int
    config_version: str
    method_version: str
    explanation: str
    requires_review: bool = True
    event_category: str = "behavior"
