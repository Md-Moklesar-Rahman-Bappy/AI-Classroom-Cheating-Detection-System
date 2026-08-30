import hashlib
from pathlib import Path

import numpy as np

from ..core.logging import get_logger
from ..schemas.models import BoundingBox, DetectionResult
from .base import ObjectDetector

logger = get_logger(__name__)

COCO_NAMES = {0: "person", 67: "cell phone"}


class UltralyticsDetector(ObjectDetector):
    def __init__(
        self,
        model_path: str = "yolo11n.pt",
        conf: float = 0.25,
        iou: float = 0.45,
        imgsz: int = 640,
        allowed_classes: list[int] | None = None,
    ):
        self.model_path = model_path
        self.conf = conf
        self.iou = iou
        self.imgsz = imgsz
        self.allowed = set(allowed_classes or [0, 67])
        self.model = None
        self.checksum: str | None = None
        self._load()

    def _checksum_file(self, p: Path) -> str | None:
        try:
            h = hashlib.sha256()
            with open(p, "rb") as f:
                for chunk in iter(lambda: f.read(8192), b""):
                    h.update(chunk)
            return h.hexdigest()
        except Exception:
            return None

    def _load(self) -> None:
        try:
            from ultralytics import YOLO

            p = Path(self.model_path)
            if p.exists():
                self.checksum = self._checksum_file(p)
            self.model = YOLO(self.model_path)
            logger.info(f"Loaded model {self.model_path} checksum={self.checksum}")
        except Exception as e:
            logger.error(f"Failed to load model {self.model_path}: {e}")
            self.model = None
            raise

    def is_loaded(self) -> bool:
        return self.model is not None

    def detect(self, frame: np.ndarray) -> list[DetectionResult]:
        if self.model is None:
            raise RuntimeError("Model not loaded")
        results = self.model.predict(
            frame, conf=self.conf, iou=self.iou, imgsz=self.imgsz, verbose=False
        )
        out: list[DetectionResult] = []
        for r in results:
            boxes = r.boxes
            if boxes is None:
                continue
            for box in boxes:
                cls = int(box.cls.item())
                if cls not in self.allowed:
                    continue
                conf = float(box.conf.item())
                x1, y1, x2, y2 = [float(v) for v in box.xyxy[0].tolist()]
                out.append(
                    DetectionResult(
                        class_id=cls,
                        class_name=COCO_NAMES.get(cls, str(cls)),
                        confidence=conf,
                        bbox=BoundingBox(x_min=x1, y_min=y1, x_max=x2, y_max=y2),
                    )
                )
        return out
