from pathlib import Path
from typing import Iterator

import cv2

from ..core.logging import get_logger
from .base import FramePacket, InputSource, VideoMetadata

logger = get_logger(__name__)


class RecordedVideoInput(InputSource):
    def __init__(self, path: str | Path):
        self.path = Path(path)
        self.cap: cv2.VideoCapture | None = None
        self._meta: VideoMetadata | None = None

    def open(self) -> None:
        if not self.path.exists():
            raise FileNotFoundError(f"Video file not found: {self.path}")
        if self.path.stat().st_size == 0:
            raise ValueError(f"Video file is empty: {self.path}")
        self.cap = cv2.VideoCapture(str(self.path))
        if not self.cap.isOpened():
            raise ValueError(f"Cannot open video stream: {self.path}")
        w = int(self.cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        h = int(self.cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        fps = self.cap.get(cv2.CAP_PROP_FPS) or 30.0
        count = int(self.cap.get(cv2.CAP_PROP_FRAME_COUNT))
        if count <= 0:
            count = -1
        fourcc = int(self.cap.get(cv2.CAP_PROP_FOURCC))
        codec = "".join([chr((fourcc >> 8 * i) & 0xFF) for i in range(4)])
        duration = (count / fps) if count > 0 and fps > 0 else 0.0
        if w == 0 or h == 0:
            self.cap.release()
            raise ValueError(f"Invalid video dimensions: {w}x{h}")
        self._meta = VideoMetadata(
            width=w,
            height=h,
            fps=fps,
            frame_count=count,
            codec=codec.strip(),
            duration_seconds=duration,
        )
        logger.info(f"Opened recorded video w={w} h={h} fps={fps} frames={count}")

    def metadata(self) -> VideoMetadata:
        if self._meta is None:
            raise RuntimeError("Video not opened")
        return self._meta

    def frames(self) -> Iterator[FramePacket]:
        if self.cap is None:
            raise RuntimeError("Video not opened")
        idx = 0
        while True:
            ok, frame = self.cap.read()
            if not ok or frame is None:
                break
            ts = idx / (self._meta.fps if self._meta and self._meta.fps else 30.0)
            yield FramePacket(frame=frame, frame_index=idx, timestamp_seconds=ts)
            idx += 1

    def close(self) -> None:
        if self.cap is not None:
            self.cap.release()
            self.cap = None
            logger.info("Released VideoCapture")
