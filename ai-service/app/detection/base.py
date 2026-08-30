from abc import ABC, abstractmethod

import numpy as np

from ..schemas.models import DetectionResult


class ObjectDetector(ABC):
    @abstractmethod
    def detect(self, frame: np.ndarray) -> list[DetectionResult]: ...

    @abstractmethod
    def is_loaded(self) -> bool: ...
