from typing import Iterator

import cv2

from ..core.logging import get_logger
from .base import FramePacket, InputSource, VideoMetadata

logger = get_logger(__name__)


class RtspStreamInput(InputSource):
    def __init__(self, url: str, timeout_ms: int = 5000):
        if not url:
            raise ValueError("RTSP URL must be non-empty")
        if url.count("://") == 0:
            raise ValueError("Invalid stream URL")
        self.url = url
        self.timeout_ms = timeout_ms
        self.cap: cv2.VideoCapture | None = None
        self._meta: VideoMetadata | None = None

    def open(self) -> None:
        logger.info("Attempting to open stream [REDACTED]")
        self.cap = cv2.VideoCapture(self.url)
        if not self.cap.isOpened():
            raise ValueError("Cannot open stream [REDACTED]")
        w = int(self.cap.get(cv2.CAP_PROP_FRAME_WIDTH)) or 640
        h = int(self.cap.get(cv2.CAP_PROP_FRAME_HEIGHT)) or 360
        fps = self.cap.get(cv2.CAP_PROP_FPS) or 25.0
        self._meta = VideoMetadata(
            width=w,
            height=h,
            fps=fps,
            frame_count=-1,
            codec="h264",
            duration_seconds=0.0,
        )
        logger.info(f"Opened stream w={w} h={h}")

    def metadata(self) -> VideoMetadata:
        if self._meta is None:
            raise RuntimeError("Stream not opened")
        return self._meta

    def frames(self) -> Iterator[FramePacket]:
        if self.cap is None:
            raise RuntimeError("Stream not opened")
        idx = 0
        while True:
            ok, frame = self.cap.read()
            if not ok or frame is None:
                break
            ts = idx / (self._meta.fps if self._meta else 25.0)
            yield FramePacket(frame=frame, frame_index=idx, timestamp_seconds=ts)
            idx += 1

    def close(self) -> None:
        if self.cap is not None:
            self.cap.release()
            self.cap = None
            logger.info("Released stream")
