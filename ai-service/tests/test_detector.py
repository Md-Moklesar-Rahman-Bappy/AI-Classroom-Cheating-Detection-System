import numpy as np
from app.detection.yolo_detector import UltralyticsDetector


def test_detector_load():
    det = UltralyticsDetector(model_path="yolo11n.pt")
    assert det.is_loaded()
    assert det.checksum is not None


def test_detector_failure():
    try:
        UltralyticsDetector(model_path="nonexistent_weights_xyz.pt")
        assert False
    except Exception:
        pass


def test_detection_result_mapping():
    det = UltralyticsDetector(model_path="yolo11n.pt")
    frame = np.zeros((360, 640, 3), dtype=np.uint8)
    dets = det.detect(frame)
    assert isinstance(dets, list)
    for d in dets:
        assert d.class_id in [0, 67]
        assert 0 <= d.confidence <= 1


def test_resize_behavior():
    from app.inputs.scheduler import FrameScheduler
    import cv2

    sched = FrameScheduler(process_every_n_frames=1, target_width=320, target_height=240)
    frame = np.zeros((480, 640, 3), dtype=np.uint8)
    out = sched.preprocess(frame)
    assert out.shape[1] == 320 and out.shape[0] == 240
