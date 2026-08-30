import os
import tempfile

import cv2
import numpy as np

from app.metrics.collector import MetricsCollector
from app.rendering.renderer import BoundingBoxRenderer
from app.schemas.models import BoundingBox, DetectionResult


def test_renderer():
    r = BoundingBoxRenderer()
    frame = np.zeros((100, 100, 3), dtype=np.uint8)
    det = DetectionResult(
        class_id=0,
        class_name="person",
        confidence=0.9,
        bbox=BoundingBox(x_min=10, y_min=10, x_max=50, y_max=50),
    )
    out = r.render(frame, [det])
    assert out.shape == frame.shape
    assert not np.array_equal(out, frame)


def test_output_writer():
    p = tempfile.mktemp(suffix=".mp4")
    fourcc = cv2.VideoWriter_fourcc(*"mp4v")
    w = cv2.VideoWriter(p, fourcc, 10, (64, 48))
    assert w.isOpened()
    w.write(np.zeros((48, 64, 3), dtype=np.uint8))
    w.release()
    assert os.path.exists(p)
    cap = cv2.VideoCapture(p)
    ok, _ = cap.read()
    assert ok
    cap.release()
    os.remove(p)
    # writer failure path
    bad = cv2.VideoWriter("/nonexistent_dir/out.mp4", fourcc, 10, (64, 48))
    assert not bad.isOpened()


def test_metrics():
    m = MetricsCollector()
    for _ in range(5):
        m.tick(True)
    snap = m.snapshot()
    assert snap["frames_processed"] == 5
    assert "processing_fps" in snap
