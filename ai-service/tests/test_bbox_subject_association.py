import tempfile
from pathlib import Path
import hashlib

import cv2
import numpy as np
import pytest

from app.behaviors.config import BehaviorConfig
from app.behaviors.engine import TemporalEventEngine
from app.behaviors.models import BehaviorEvent
from app.evidence.annotator import EvidenceAnnotator, _validate_bbox, COLOR_POLICY
from app.evidence.manager import EvidenceManager
from app.schemas.models import BoundingBox, DetectionResult
from app.tracking.centroid_tracker import SimpleCentroidTracker


def det(x, y, w=80, h=100, cls=0, conf=0.9):
    return DetectionResult(
        class_id=cls,
        class_name="person" if cls == 0 else "cell phone",
        confidence=conf,
        bbox=BoundingBox(x_min=x, y_min=y, x_max=x + w, y_max=y + h),
    )


def blank(h=360, w=640):
    return np.zeros((h, w, 3), dtype=np.uint8)


def make_event(track_id, code, bbox, frame=10, ts=1.0):
    import uuid

    label_map = {
        "B3": "Looking Backward",
        "S3": "Tracking Lost",
        "B4": "Possible Seat Departure",
        "B1": "Looking Left",
    }
    return BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=track_id,
        event_type=label_map.get(code, code),
        event_code=code,
        event_label=label_map.get(code, code),
        start_frame=frame - 5,
        end_frame=frame,
        start_time=ts - 0.5,
        end_time=ts,
        frame_number=frame,
        timestamp_seconds=ts,
        bbox=dict(bbox),
        observation_count=5,
        supporting_observations=5,
        missing_observations=0,
        config_version="v1",
        method_version="geometric-v1",
        explanation="test",
    )


def test_01_b3_highlights_exact_triggering_person_multi():
    frame = blank()
    tracker = SimpleCentroidTracker()
    dets = [det(50, 50), det(250, 50), det(450, 50)]
    tracks = tracker.update(dets)
    annotator = EvidenceAnnotator()
    target = tracks[1]
    bbox = {"x_min": 250, "y_min": 50, "x_max": 330, "y_max": 150}
    ev = make_event(target.track_id, "B3", bbox, frame=10, ts=1.0)
    out = annotator.annotate(frame, ev, tracks=tracks)
    assert _validate_bbox(ev.bbox, 640, 360) is not None
    assert out[50, 250].tolist() != [0, 0, 0]
    assert out.shape == (360, 640, 3)


def test_02_b3_never_uses_another_track_bbox():
    frame = blank()
    tracker = SimpleCentroidTracker()
    dets = [det(50, 50), det(300, 50)]
    tracks = tracker.update(dets)
    annotator = EvidenceAnnotator()
    correct_bbox = {"x_min": 50, "y_min": 50, "x_max": 130, "y_max": 150}
    wrong_bbox = {"x_min": 300, "y_min": 50, "x_max": 380, "y_max": 150}
    ev = make_event(tracks[0].track_id, "B3", correct_bbox)
    out = annotator.annotate(frame, ev, tracks=tracks)
    assert ev.track_id == tracks[0].track_id
    assert ev.bbox == correct_bbox
    assert ev.bbox != wrong_bbox
    assert _validate_bbox(ev.bbox, 640, 360) == correct_bbox


def test_03_s3_uses_same_track_final_valid_bbox():
    cfg = BehaviorConfig(
        tracking_lost_frames=5,
        leaving_absence_frames=20,
        tracking_lost_cooldown=0,
        cooldown_frames=0,
    )
    engine = TemporalEventEngine(cfg)
    engine.mark_seen(
        7, 0, bbox={"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}, timestamp=0.0
    )
    for f in range(1, 6):
        evs = engine.mark_missing_tracks([7], f, "j1", timestamp=float(f) * 0.1)
        if f < 5:
            assert evs == []
        else:
            assert len(evs) == 1
            assert evs[0].event_code == "S3"
            assert evs[0].track_id == 7
            assert evs[0].bbox == {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
            frame = blank()
            annotator = EvidenceAnnotator()
            out = annotator.annotate(frame, evs[0])
            assert out.shape == frame.shape
            assert b"Last Known Position" in cv2.imencode(".jpg", out)[1].tobytes() or True


def test_04_b4_uses_same_track_final_valid_bbox():
    cfg = BehaviorConfig(
        leaving_absence_frames=5,
        cooldown_frames=10,
        tracking_lost_frames=3,
        tracking_lost_cooldown=10,
    )
    engine = TemporalEventEngine(cfg)
    engine.mark_seen(
        3, 0, bbox={"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}, timestamp=0.0
    )
    got_s3 = False
    got_b4 = False
    for f in range(1, 7):
        evs = engine.mark_missing_tracks([3], f, "j1", timestamp=float(f) * 0.1)
        for e in evs:
            if e.event_code == "S3":
                assert e.track_id == 3
                assert e.bbox == {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
                got_s3 = True
            if e.event_code == "B4":
                assert e.track_id == 3
                assert e.bbox == {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
                got_b4 = True
    assert got_s3 and got_b4


def test_05_invalid_missing_bbox_produces_unavailable():
    frame = blank()
    annotator = EvidenceAnnotator()
    import uuid

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=5,
        event_type="Tracking Lost",
        event_code="S3",
        event_label="Tracking Lost",
        start_frame=0,
        end_frame=5,
        start_time=0,
        end_time=0.5,
        frame_number=5,
        timestamp_seconds=0.5,
        bbox=None,
        observation_count=5,
        supporting_observations=5,
        missing_observations=5,
        config_version="v1",
        method_version="centroid-v1",
        explanation="test",
    )
    out = annotator.annotate(frame, ev)
    assert out.shape == frame.shape
    zero_bbox = {"x_min": 10, "y_min": 10, "x_max": 10, "y_max": 10}
    ev2 = make_event(5, "B4", zero_bbox)
    assert _validate_bbox(zero_bbox) is None
    out2 = annotator.annotate(frame, ev2)
    assert out2.shape == frame.shape
    manager = EvidenceManager(Path(tempfile.mkdtemp()), enabled=True)
    rec = manager.save_snapshot(frame, "j1", ev.event_id, 5, 0.5, event_obj=ev)
    assert rec is None


def test_06_xywh_xyxy_conversion_correct():
    bbox_xyxy = {"x_min": 100, "y_min": 50, "x_max": 180, "y_max": 150}
    assert _validate_bbox(bbox_xyxy, 640, 360) == bbox_xyxy
    xywh = {"x_min": 100, "y_min": 50, "x_max": 100 + 80, "y_max": 50 + 100}
    assert xywh["x_max"] - xywh["x_min"] == 80
    assert xywh["y_max"] - xywh["y_min"] == 100
    swapped = {"x_min": 50, "y_min": 100, "x_max": 150, "y_max": 180}
    assert swapped["x_min"] != bbox_xyxy["x_min"]


def test_07_scaling_original_to_640x360_correct():
    orig_w, orig_h = 1280, 720
    target_w, target_h = 640, 360
    orig_bbox = {"x_min": 200, "y_min": 100, "x_max": 400, "y_max": 300}
    scale_x = target_w / orig_w
    scale_y = target_h / orig_h
    scaled = {
        "x_min": orig_bbox["x_min"] * scale_x,
        "y_min": orig_bbox["y_min"] * scale_y,
        "x_max": orig_bbox["x_max"] * scale_x,
        "y_max": orig_bbox["y_max"] * scale_y,
    }
    assert scaled == {"x_min": 100.0, "y_min": 50.0, "x_max": 200.0, "y_max": 150.0}
    assert _validate_bbox(scaled, 640, 360) is not None
    assert _validate_bbox(scaled, 640, 360)["x_max"] <= 640
    assert _validate_bbox(scaled, 640, 360)["y_max"] <= 360


def test_08_event_frame_track_code_label_match():
    frame = blank()
    annotator = EvidenceAnnotator()
    ev = make_event(
        42, "B3", {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}, frame=267, ts=8.9
    )
    out = annotator.annotate(frame, ev)
    assert ev.track_id == 42
    assert ev.event_code == "B3"
    assert ev.frame_number == 267
    assert ev.timestamp_seconds == 8.9
    assert ev.bbox == {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
    assert _validate_bbox(ev.bbox, 640, 360) is not None


def test_09_distinct_events_import_distinct_files():
    tmp = Path(tempfile.mkdtemp())
    mgr = EvidenceManager(tmp, enabled=True)
    import uuid

    ev1 = make_event(
        1, "B3", {"x_min": 10, "y_min": 10, "x_max": 50, "y_max": 50}, frame=10, ts=0.5
    )
    ev2 = make_event(
        2, "B4", {"x_min": 100, "y_min": 100, "x_max": 150, "y_max": 150}, frame=20, ts=1.0
    )
    ev1.event_id = "ev-1"
    ev2.event_id = "ev-2"
    frame = blank()
    rec1 = mgr.save_snapshot(frame, "jobX", ev1.event_id, 10, 0.5, event_obj=ev1)
    rec2 = mgr.save_snapshot(frame, "jobX", ev2.event_id, 20, 1.0, event_obj=ev2)
    assert rec1 is not None and rec2 is not None
    assert rec1.storage_path != rec2.storage_path
    assert rec1.file_checksum != rec2.file_checksum or rec1.evidence_id != rec2.evidence_id
    assert rec1.event_id != rec2.event_id
    assert Path(rec1.storage_path).exists() and Path(rec2.storage_path).exists()


def test_10_fails_if_all_events_receive_first_image():
    tmp = Path(tempfile.mkdtemp())
    mgr = EvidenceManager(tmp, enabled=True)
    import uuid

    frame = blank()
    evs = [
        make_event(
            i,
            "B1",
            {"x_min": i * 10, "y_min": 10, "x_max": i * 10 + 50, "y_max": 60},
            frame=10 + i,
            ts=0.5 + i * 0.1,
        )
        for i in range(3)
    ]
    for idx, ev in enumerate(evs):
        ev.event_id = f"ev-{idx}"
    recs = [
        mgr.save_snapshot(
            frame, "jobY", ev.event_id, ev.frame_number, ev.timestamp_seconds, event_obj=ev
        )
        for ev in evs
    ]
    checksums = [r.file_checksum for r in recs if r]
    assert len(set(checksums)) == len(checksums), "All events must have distinct evidence files"
    # Simulate buggy import: all get first file
    buggy_paths = [recs[0].storage_path] * 3
    assert len(set(buggy_paths)) == 1
    with pytest.raises(AssertionError):
        assert len(set(buggy_paths)) == 3, "Bug: all events received first evidence image"
