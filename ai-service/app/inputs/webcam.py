from typing import Iterator

import cv2

from ..core.logging import get_logger
from .base import FramePacket, InputSource, VideoMetadata

logger = get_logger(__name__)


class WebcamInput(InputSource):
    def __init__(self, device_index: int = 0):
        self.device_index = device_index
        self.cap: cv2.VideoCapture | None = None
        self._meta: VideoMetadata | None = None

    def open(self) -> None:
        self.cap = cv2.VideoCapture(self.device_index)
        if not self.cap.isOpened():
            raise ValueError(f"Cannot open webcam device {self.device_index}")
        w = int(self.cap.get(cv2.CAP_PROP_FRAME_WIDTH)) or 640
        h = int(self.cap.get(cv2.CAP_PROP_FRAME_HEIGHT)) or 480
        fps = self.cap.get(cv2.CAP_PROP_FPS) or 30.0
        self._meta = VideoMetadata(
            width=w,
            height=h,
            fps=fps,
            frame_count=-1,
            codec="raw",
            duration_seconds=0.0,
        )
        logger.info(f"Opened webcam {self.device_index} w={w} h={h}")

    def metadata(self) -> VideoMetadata:
        if self._meta is None:
            raise RuntimeError("Webcam not opened")
        return self._meta

    def frames(self) -> Iterator[FramePacket]:
        if self.cap is None:
            raise RuntimeError("Webcam not opened")
        idx = 0
        while True:
            ok, frame = self.cap.read()
            if not ok or frame is None:
                break
            ts = idx / (self._meta.fps if self._meta else 30.0)
            yield FramePacket(frame=frame, frame_index=idx, timestamp_seconds=ts)
            idx += 1

    def close(self) -> None:
        if self.cap is not None:
            self.cap.release()
            self.cap = None
            logger.info("Released webcam")
