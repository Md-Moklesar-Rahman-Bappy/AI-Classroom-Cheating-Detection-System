import math

from ..schemas.models import DetectionResult
from .models import DetectionEvent


def _centroid(bbox: dict) -> tuple[float, float]:
    return ((bbox["x_min"] + bbox["x_max"]) / 2, (bbox["y_min"] + bbox["y_max"]) / 2)


def _bbox_dict(bbox) -> dict:
    if hasattr(bbox, "model_dump"):
        return bbox.model_dump()
    if isinstance(bbox, dict):
        return dict(bbox)
    return dict(bbox)


def _iou(a: dict, b: dict) -> float:
    x1 = max(a["x_min"], b["x_min"])
    y1 = max(a["y_min"], b["y_min"])
    x2 = min(a["x_max"], b["x_max"])
    y2 = min(a["y_max"], b["y_max"])
    if x2 <= x1 or y2 <= y1:
        return 0.0
    inter = (x2 - x1) * (y2 - y1)
    area_a = (a["x_max"] - a["x_min"]) * (a["y_max"] - a["y_min"])
    area_b = (b["x_max"] - b["x_min"]) * (b["y_max"] - b["y_min"])
    union = area_a + area_b - inter
    return inter / union if union > 0 else 0.0


def associate_phone_to_nearest_track(
    phone: DetectionResult, tracks: list, max_distance: float = 300
) -> tuple[int | None, dict | None]:
    if not tracks:
        return None, None
    pb = phone.bbox
    px, py = (pb.x_min + pb.x_max) / 2, (pb.y_min + pb.y_max) / 2
    best_id = None
    best_bbox = None
    best_dist = float("inf")
    for tr in tracks:
        tb = tr.bbox.bbox
        tx, ty = (tb.x_min + tb.x_max) / 2, (tb.y_min + tb.y_max) / 2
        dist = math.hypot(px - tx, py - ty)
        if dist < best_dist and dist < max_distance:
            best_dist = dist
            best_id = tr.track_id
            best_bbox = _bbox_dict(tb)
    return best_id, best_bbox


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


class MultiplePersonsRule:
    def __init__(self, threshold: int = 2, iou_threshold: float = 0.3, cooldown_frames: int = 30):
        self.threshold = threshold
        self.iou_threshold = iou_threshold
        self.cooldown = cooldown_frames
        self._last_event_frame: int | None = None

    def should_emit(
        self, frame_index: int, detections: list[DetectionResult], tracks: list | None = None
    ) -> bool:
        persons = [d for d in detections if d.class_id == 0]
        if len(persons) < self.threshold:
            return False
        if (
            self._last_event_frame is not None
            and (frame_index - self._last_event_frame) < self.cooldown
        ):
            return False
        has_overlap = False
        for i in range(len(persons)):
            for j in range(i + 1, len(persons)):
                a = _bbox_dict(persons[i].bbox)
                b = _bbox_dict(persons[j].bbox)
                if _iou(a, b) > self.iou_threshold:
                    has_overlap = True
                    break
        if len(persons) >= self.threshold:
            return True
        return has_overlap

    def record_emission(self, frame_index: int) -> None:
        self._last_event_frame = frame_index


def create_events_for_detections(
    job_id: str,
    frame_index: int,
    timestamp: float,
    detections: list[DetectionResult],
    tracks: list | None = None,
) -> list[DetectionEvent]:
    events: list[DetectionEvent] = []
    for d in detections:
        if d.class_id != 67:
            continue
        track_id, track_bbox = None, None
        if tracks is not None:
            track_id, track_bbox = associate_phone_to_nearest_track(d, tracks)
        events.append(
            DetectionEvent.create_mobile_phone(
                job_id=job_id,
                frame_number=frame_index,
                timestamp_seconds=timestamp,
                class_id=d.class_id,
                class_name=d.class_name,
                confidence=d.confidence,
                bbox=_bbox_dict(d.bbox),
                track_id=track_id,
                associated_track_bbox=track_bbox,
            )
        )
    return events


def create_d3_events(
    job_id: str,
    frame_index: int,
    timestamp: float,
    detections: list[DetectionResult],
    tracks: list | None = None,
    seat_region: dict | None = None,
) -> list[DetectionEvent]:
    persons = [d for d in detections if d.class_id == 0]
    if len(persons) < 2:
        return []
    if seat_region:
        inside = []
        for p in persons:
            b = _bbox_dict(p.bbox)
            cx = (b["x_min"] + b["x_max"]) / 2
            cy = (b["y_min"] + b["y_max"]) / 2
            if (
                seat_region["x_min"] <= cx <= seat_region["x_max"]
                and seat_region["y_min"] <= cy <= seat_region["y_max"]
            ):
                inside.append(p)
        if len(inside) < 2:
            return []
        persons = inside
    xs = [p.bbox.x_min for p in persons]
    ys = [p.bbox.y_min for p in persons]
    xe = [p.bbox.x_max for p in persons]
    ye = [p.bbox.y_max for p in persons]
    bbox = {
        "x_min": float(min(xs)),
        "y_min": float(min(ys)),
        "x_max": float(max(xe)),
        "y_max": float(max(ye)),
    }
    track_ids = [t.track_id for t in (tracks or [])]
    avg_conf = sum(p.confidence for p in persons) / len(persons) if persons else 0.9
    return [
        DetectionEvent.create_multiple_persons(
            job_id, frame_index, timestamp, bbox, track_ids=track_ids, confidence=avg_conf
        )
    ]
