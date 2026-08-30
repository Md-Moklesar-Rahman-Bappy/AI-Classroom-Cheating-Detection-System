import math

from ..schemas.models import DetectionResult
from .base import Track, Tracker


def _centroid(bbox):
    return ((bbox.x_min + bbox.x_max) / 2, (bbox.y_min + bbox.y_max) / 2)


class SimpleCentroidTracker(Tracker):
    def __init__(self, max_distance: float = 80.0, max_missing: int = 10):
        self.max_distance = max_distance
        self.max_missing = max_missing
        self.next_id = 1
        self.tracks: dict[int, Track] = {}

    def reset(self) -> None:
        self.tracks.clear()
        self.next_id = 1

    def update(self, detections: list[DetectionResult]) -> list[Track]:
        person_dets = [d for d in detections if d.class_id == 0]
        if not self.tracks:
            for det in person_dets:
                tid = self.next_id
                self.next_id += 1
                self.tracks[tid] = Track(track_id=tid, bbox=det, hits=1, missing=0, age=1)
            return list(self.tracks.values())

        unmatched_tracks = set(self.tracks.keys())
        unmatched_dets = []
        for det in person_dets:
            cx, cy = _centroid(det.bbox)
            best_id = None
            best_dist = float("inf")
            for tid in list(unmatched_tracks):
                tr = self.tracks[tid]
                tcx, tcy = _centroid(tr.bbox.bbox)
                dist = math.hypot(cx - tcx, cy - tcy)
                if dist < best_dist and dist < self.max_distance:
                    best_dist = dist
                    best_id = tid
            if best_id is not None:
                tr = self.tracks[best_id]
                tr.bbox = det
                tr.hits += 1
                tr.missing = 0
                tr.age += 1
                unmatched_tracks.remove(best_id)
            else:
                unmatched_dets.append(det)

        for tid in list(unmatched_tracks):
            tr = self.tracks[tid]
            tr.missing += 1
            tr.age += 1
            if tr.missing > self.max_missing:
                del self.tracks[tid]

        for det in unmatched_dets:
            tid = self.next_id
            self.next_id += 1
            self.tracks[tid] = Track(track_id=tid, bbox=det, hits=1, missing=0, age=1)

        return list(self.tracks.values())
