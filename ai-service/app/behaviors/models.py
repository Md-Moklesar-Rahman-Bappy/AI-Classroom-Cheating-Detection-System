from dataclasses import dataclass, field


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
class TwoFrameEvidence:
    """Temporal evidence linking a historical detection frame to a trigger frame.

    For S3 and B4, the historical bbox belongs to the LAST DETECTED frame,
    not the trigger frame. This class stores both frames separately.
    """

    trigger_frame_number: int = 0
    trigger_timestamp: float = 0.0
    trigger_frame_path: str = ""
    last_detection_frame_number: int = 0
    last_detection_timestamp: float = 0.0
    last_detection_bbox: dict | None = None
    last_detection_frame_path: str = ""
    absence_processed_frames: int = 0
    absence_source_frames: list[int] = field(default_factory=list)
    bbox_format: str = "xyxy"
    processed_frame_size: dict = field(default_factory=lambda: {"width": 640, "height": 360})
    source_frame_size: dict = field(default_factory=lambda: {"width": 64, "height": 48})


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
    two_frame_evidence: TwoFrameEvidence | None = None
    last_detection_frame_number: int = 0
    last_detection_timestamp: float = 0.0
    last_detection_bbox: dict | None = None
    trigger_frame_number: int = 0
    trigger_timestamp: float = 0.0
    absence_processed_frames: int = 0
