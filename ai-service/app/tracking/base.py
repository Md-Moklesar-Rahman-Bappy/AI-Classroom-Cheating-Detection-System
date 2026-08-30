from abc import ABC, abstractmethod
from dataclasses import dataclass

from ..schemas.models import DetectionResult


@dataclass
class Track:
    track_id: int
    bbox: DetectionResult
    age: int = 0
    hits: int = 1
    missing: int = 0


class Tracker(ABC):
    @abstractmethod
    def update(self, detections: list[DetectionResult]) -> list[Track]: ...

    @abstractmethod
    def reset(self) -> None: ...
