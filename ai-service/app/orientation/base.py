from abc import ABC, abstractmethod

from ..tracking.base import Track
from .models import OrientationObservation


class OrientationEstimator(ABC):
    @abstractmethod
    def estimate(self, track: Track, timestamp: float) -> OrientationObservation: ...
