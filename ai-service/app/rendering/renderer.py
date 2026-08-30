import cv2
import numpy as np

from ..schemas.models import DetectionResult

COLORS = {0: (0, 200, 0), 67: (255, 0, 0)}


class BoundingBoxRenderer:
    def render(self, frame: np.ndarray, detections: list[DetectionResult]) -> np.ndarray:
        out = frame.copy()
        for det in detections:
            b = det.bbox
            color = COLORS.get(det.class_id, (200, 200, 200))
            x1, y1, x2, y2 = int(b.x_min), int(b.y_min), int(b.x_max), int(b.y_max)
            cv2.rectangle(out, (x1, y1), (x2, y2), color, 2)
            label = f"{det.class_name} {det.confidence:.2f}"
            cv2.putText(
                out,
                label,
                (x1, max(10, y1 - 6)),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                color,
                1,
                cv2.LINE_AA,
            )
        return out
