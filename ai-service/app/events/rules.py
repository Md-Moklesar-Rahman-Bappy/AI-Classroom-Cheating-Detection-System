from ..schemas.models import DetectionResult
from .models import DetectionEvent


class MobilePhoneEventRule:
    def __init__(self, cooldown_frames: int = 30):
        if cooldown_frames < 0:
            raise ValueError("cooldown_frames must be >=0")
        self.cooldown = cooldown_frames
        self._last_event_frame: int | None = None
        self._seen_count: int = 0

    def should_emit(
        self, frame_index: int, detections: list[DetectionResult]
    ) -> list[DetectionResult]:
        phones = [d for d in detections if d.class_id == 67]
        if not phones:
            return []
        if (
            self._last_event_frame is not None
            and (frame_index - self._last_event_frame) < self.cooldown
        ):
            return []
        return phones

    def record_emission(self, frame_index: int) -> None:
        self._last_event_frame = frame_index
        self._seen_count += 1

    def suppression_stats(self) -> dict:
        return {"events_emitted": self._seen_count, "cooldown": self.cooldown}


def create_events_for_detections(
    job_id: str, frame_index: int, timestamp: float, detections: list[DetectionResult]
) -> list[DetectionEvent]:
    events: list[DetectionEvent] = []
    for d in detections:
        if d.class_id != 67:
            continue
        events.append(
            DetectionEvent.create_mobile_phone(
                job_id=job_id,
                frame_number=frame_index,
                timestamp_seconds=timestamp,
                class_id=d.class_id,
                class_name=d.class_name,
                confidence=d.confidence,
                bbox=d.bbox.model_dump() if hasattr(d.bbox, "model_dump") else dict(d.bbox),
            )
        )
    return events
