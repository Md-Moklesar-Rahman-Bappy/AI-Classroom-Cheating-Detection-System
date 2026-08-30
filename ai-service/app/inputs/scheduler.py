from typing import Iterator

import cv2
import numpy as np

from .base import FramePacket


class FrameScheduler:
    def __init__(
        self,
        process_every_n_frames: int = 3,
        target_width: int = 640,
        target_height: int = 360,
    ):
        if process_every_n_frames < 1:
            raise ValueError("process_every_n_frames must be >=1")
        self.n = process_every_n_frames
        self.tw = target_width
        self.th = target_height

    def should_process(self, frame_index: int) -> bool:
        return frame_index % self.n == 0

    def preprocess(self, frame: np.ndarray) -> np.ndarray:
        if frame.shape[1] != self.tw or frame.shape[0] != self.th:
            return cv2.resize(frame, (self.tw, self.th))
        return frame

    def filter(self, packets: Iterator[FramePacket]) -> Iterator[FramePacket]:
        for p in packets:
            if self.should_process(p.frame_index):
                p.frame = self.preprocess(p.frame)
                yield p
