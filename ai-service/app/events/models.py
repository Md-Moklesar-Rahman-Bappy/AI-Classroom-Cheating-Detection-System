import time
import uuid
from dataclasses import dataclass


@dataclass
class DetectionEvent:
    event_id: str
    job_id: str
    event_type: str
    frame_number: int
    timestamp_seconds: float
    class_id: int
    class_name: str
    confidence: float
    bbox: dict
    requires_review: bool = True
    created_at: float = 0

    @staticmethod
    def create_mobile_phone(
        job_id: str,
        frame_number: int,
        timestamp_seconds: float,
        class_id: int,
        class_name: str,
        confidence: float,
        bbox: dict,
    ) -> "DetectionEvent":
        return DetectionEvent(
            event_id=str(uuid.uuid4()),
            job_id=job_id,
            event_type="Mobile Phone Detected",
            frame_number=frame_number,
            timestamp_seconds=timestamp_seconds,
            class_id=class_id,
            class_name=class_name,
            confidence=confidence,
            bbox=bbox,
            requires_review=True,
            created_at=time.time(),
        )
