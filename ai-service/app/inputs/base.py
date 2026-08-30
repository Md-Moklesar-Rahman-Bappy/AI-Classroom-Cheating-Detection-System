from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import Iterator

import numpy as np


@dataclass
class VideoMetadata:
    width: int
    height: int
    fps: float
    frame_count: int
    codec: str
    duration_seconds: float


@dataclass
class FramePacket:
    frame: np.ndarray
    frame_index: int
    timestamp_seconds: float


class InputSource(ABC):
    @abstractmethod
    def open(self) -> None: ...

    @abstractmethod
    def metadata(self) -> VideoMetadata: ...

    @abstractmethod
    def frames(self) -> Iterator[FramePacket]: ...

    @abstractmethod
    def close(self) -> None: ...

    def __enter__(self):
        self.open()
        return self

    def __exit__(self, *args):
        self.close()
