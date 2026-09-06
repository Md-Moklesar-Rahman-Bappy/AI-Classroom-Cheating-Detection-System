import time
import uuid
from dataclasses import dataclass


EVENT_CODE_MAP = {
    0: "D1",
    67: "D2",
}

EVENT_LABEL_MAP = {
    "D1": "Person Detected",
    "D2": "Mobile Phone Detected",
    "D3": "Multiple Persons Detected",
    "B1": "Looking Left",
    "B2": "Looking Right",
    "B3": "Looking Backward",
    "B4": "Possible Seat Departure",
    "B5": "Excessive Head Movement",
    "S1": "Normal",
    "S2": "Insufficient Evidence",
    "S3": "Tracking Lost",
}

EVENT_CATEGORY_MAP = {
    "D1": "detection",
    "D2": "detection",
    "D3": "detection",
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
class DetectionEvent:
    event_id: str
    job_id: str
    event_type: str
    event_code: str
    frame_number: int
    timestamp_seconds: float
    class_id: int
    class_name: str
    confidence: float
    bbox: dict
    track_id: int | None = None
    associated_track_bbox: dict | None = None
    phone_bbox: dict | None = None
    requires_review: bool = True
    created_at: float = 0
    event_category: str = "detection"

    @staticmethod
    def create_mobile_phone(
        job_id: str,
        frame_number: int,
        timestamp_seconds: float,
        class_id: int,
        class_name: str,
        confidence: float,
        bbox: dict,
        track_id: int | None = None,
        associated_track_bbox: dict | None = None,
    ) -> "DetectionEvent":
        phone_bbox = dict(bbox)
        effective_bbox = dict(associated_track_bbox) if associated_track_bbox else dict(bbox)
        return DetectionEvent(
            event_id=str(uuid.uuid4()),
            job_id=job_id,
            event_type="Mobile Phone Detected",
            event_code="D2",
            frame_number=frame_number,
            timestamp_seconds=timestamp_seconds,
            class_id=class_id,
            class_name=class_name,
            confidence=confidence,
            bbox=effective_bbox,
            track_id=track_id,
            associated_track_bbox=associated_track_bbox,
            phone_bbox=phone_bbox,
            requires_review=True,
            created_at=time.time(),
            event_category="detection",
        )

    @staticmethod
    def create_person(
        job_id: str,
        frame_number: int,
        timestamp_seconds: float,
        class_id: int,
        class_name: str,
        confidence: float,
        bbox: dict,
        track_id: int | None = None,
    ) -> "DetectionEvent":
        return DetectionEvent(
            event_id=str(uuid.uuid4()),
            job_id=job_id,
            event_type="Person Detected",
            event_code="D1",
            frame_number=frame_number,
            timestamp_seconds=timestamp_seconds,
            class_id=class_id,
            class_name=class_name,
            confidence=confidence,
            bbox=dict(bbox),
            track_id=track_id,
            associated_track_bbox=dict(bbox) if track_id is not None else None,
            phone_bbox=None,
            requires_review=True,
            created_at=time.time(),
            event_category="detection",
        )

    @staticmethod
    def create_multiple_persons(
        job_id: str,
        frame_number: int,
        timestamp_seconds: float,
        bbox: dict,
        track_ids: list[int] | None = None,
        confidence: float = 0.9,
    ) -> "DetectionEvent":
        primary_tid = track_ids[0] if track_ids else None
        return DetectionEvent(
            event_id=str(uuid.uuid4()),
            job_id=job_id,
            event_type="Multiple Persons Detected",
            event_code="D3",
            frame_number=frame_number,
            timestamp_seconds=timestamp_seconds,
            class_id=0,
            class_name="person",
            confidence=confidence,
            bbox=dict(bbox),
            track_id=primary_tid,
            associated_track_bbox=dict(bbox),
            phone_bbox=None,
            requires_review=True,
            created_at=time.time(),
            event_category="detection",
        )
