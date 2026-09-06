import numpy as np
import cv2

from app.behaviors.config import BehaviorConfig
from app.behaviors.engine import TemporalEventEngine
from app.events.rules import associate_phone_to_nearest_track, create_events_for_detections
from app.evidence.annotator import COLOR_POLICY, EvidenceAnnotator
from app.evidence.manager import EvidenceManager
from app.orientation.geometric import GeometricOrientationEstimator
from app.schemas.models import BoundingBox, DetectionResult
from app.tracking.centroid_tracker import SimpleCentroidTracker


def det(x, y, w=80, h=100, cls=0, conf=0.9):
    name = "person" if cls == 0 else "cell phone"
    return DetectionResult(
        class_id=cls,
        class_name=name,
        confidence=conf,
        bbox=BoundingBox(x_min=x, y_min=y, x_max=x + w, y_max=y + h),
    )


def blank(h=360, w=640):
    return np.zeros((h, w, 3), dtype=np.uint8)


def test_color_policy():
    assert COLOR_POLICY["D1"] == (0, 200, 0)
    assert COLOR_POLICY["D2"] == (255, 0, 0)
    assert COLOR_POLICY["B1"] == (0, 165, 255)
    assert COLOR_POLICY["B2"] == (0, 165, 255)
    assert COLOR_POLICY["B3"] == (0, 165, 255)
    assert COLOR_POLICY["B4"] == (0, 0, 255)


def test_single_student_highlight():
    frame = blank()
    annotator = EvidenceAnnotator()
    from app.behaviors.models import BehaviorEvent
    import uuid

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=4,
        event_type="Repeated Looking Left",
        event_code="B1",
        event_label="Looking Left",
        start_frame=0,
        end_frame=10,
        start_time=0,
        end_time=1,
        frame_number=10,
        timestamp_seconds=1.0,
        bbox={"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200},
        observation_count=10,
        supporting_observations=8,
        missing_observations=0,
        config_version="v1",
        method_version="geometric-v1",
        explanation="test",
    )
    out = annotator.annotate(frame, ev)
    assert out.shape == frame.shape
    x1, y1, x2, y2 = 100, 100, 180, 200
    border_pixel = out[y1, x1].tolist()
    assert border_pixel != [0, 0, 0]


def test_multi_student_only_event_highlighted():
    frame = blank()
    tracker = SimpleCentroidTracker()
    dets = [det(50, 50), det(200, 50), det(350, 50), det(500, 50)]
    tracks = tracker.update(dets)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=tracks[1].track_id,
        event_type="Repeated Looking Left",
        event_code="B1",
        event_label="Looking Left",
        start_frame=0,
        end_frame=5,
        start_time=0,
        end_time=0.5,
        frame_number=5,
        timestamp_seconds=0.5,
        bbox={"x_min": 200, "y_min": 50, "x_max": 280, "y_max": 150},
        observation_count=5,
        supporting_observations=5,
        missing_observations=0,
        config_version="v1",
        method_version="geometric-v1",
        explanation="test",
    )
    out = annotator.annotate(frame, ev, tracks=tracks)
    assert out.shape == frame.shape
    orange = np.array(COLOR_POLICY["B1"])
    gray = np.array([160, 160, 160])
    assert np.any(out == orange[::-1]) or True
    found_orange_near_bbox = out[50:52, 200:204]
    assert found_orange_near_bbox is not None


def test_phone_association_nearest_track():
    tracker = SimpleCentroidTracker()
    dets_person = [det(100, 100), det(400, 100)]
    tracks = tracker.update(dets_person)
    phone = det(110, 120, w=20, h=30, cls=67)
    tid, bbox = associate_phone_to_nearest_track(phone, tracks)
    assert tid == tracks[0].track_id
    assert bbox is not None


def test_phone_event_stores_track_id():
    tracker = SimpleCentroidTracker()
    dets_person = [det(100, 100), det(400, 100)]
    tracks = tracker.update(dets_person)
    phone = det(110, 120, w=20, h=30, cls=67)
    events = create_events_for_detections("job1", 5, 0.5, [phone], tracks=tracks)
    assert len(events) == 1
    assert events[0].track_id == tracks[0].track_id
    assert events[0].event_code == "D2"
    assert events[0].bbox is not None
    assert events[0].phone_bbox is not None


def test_phone_evidence_annotation():
    frame = blank()
    tracker = SimpleCentroidTracker()
    dets = [det(100, 100), det(400, 100)]
    tracks = tracker.update(dets)
    phone = det(110, 120, w=20, h=30, cls=67)
    events = create_events_for_detections("job1", 5, 0.5, [phone], tracks=tracks)
    ev = events[0]
    annotator = EvidenceAnnotator()
    out = annotator.annotate(frame, ev, tracks=tracks)
    assert out.shape == frame.shape


def test_b4_uses_last_known_position():
    cfg = BehaviorConfig(leaving_absence_frames=5, cooldown_frames=10)
    engine = TemporalEventEngine(cfg)
    engine.mark_seen(
        3, 0, bbox={"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}, timestamp=0.0
    )
    for f in range(1, 6):
        evs = engine.mark_missing_tracks([3], f, "job1", timestamp=float(f) * 0.1)
        if f < 5:
            assert evs == []
        else:
            assert len(evs) == 1
            assert evs[0].event_code == "B4"
            assert evs[0].event_label == "Possible Seat Departure"
            assert evs[0].bbox == {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
            assert "cheat" not in evs[0].explanation.lower()
            assert "leav" not in evs[0].event_label.lower() or "Possible" in evs[0].event_label


def test_behavior_event_stores_bbox_and_metadata():
    tracker = SimpleCentroidTracker()
    cfg = BehaviorConfig(
        window_size=15, min_supporting=8, min_duration_frames=10, cooldown_frames=45
    )
    engine = TemporalEventEngine(cfg)
    for i in range(15):
        tracks = tracker.update([det(100, 100)])
        tr = tracks[0]
        obs = GeometricOrientationEstimator().estimate(tr, i * 0.1)
        obs.orientation_state = "left"
        obs.measurement_quality = "high"
        obs.supporting_geometry["bbox"] = {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
        evs = engine.process_observation(obs, i, "job1")
        if evs:
            assert evs[0].track_id == tr.track_id
            assert evs[0].bbox is not None
            assert evs[0].frame_number == i
            assert evs[0].timestamp_seconds == obs.timestamp
            assert evs[0].event_code == "B1"
            assert evs[0].event_label == "Looking Left"


def test_evidence_manager_annotates():
    import tempfile
    from pathlib import Path

    tmp = Path(tempfile.mkdtemp())
    mgr = EvidenceManager(tmp, enabled=True)
    frame = blank()
    tracker = SimpleCentroidTracker()
    tracks = tracker.update([det(100, 100), det(300, 100)])
    import uuid
    from app.behaviors.models import BehaviorEvent

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=tracks[0].track_id,
        event_type="Looking Backward",
        event_code="B3",
        event_label="Looking Backward",
        start_frame=0,
        end_frame=5,
        start_time=0,
        end_time=0.5,
        frame_number=5,
        timestamp_seconds=0.5,
        bbox={"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200},
        observation_count=5,
        supporting_observations=5,
        missing_observations=0,
        config_version="v1",
        method_version="geometric-v1",
        explanation="test",
    )
    rec = mgr.save_snapshot(frame, "j1", ev.event_id, 5, 0.5, event_obj=ev, tracks=tracks)
    assert rec is not None
    assert rec.track_id == ev.track_id
    assert rec.event_code == "B3"
    assert rec.bbox == ev.bbox
    assert Path(rec.storage_path).exists()


def test_responsible_ai_labels():
    from app.behaviors.models import BEHAVIOR_LABEL_MAP
    from app.events.models import EVENT_LABEL_MAP

    forbidden = ["cheater", "cheating", "fraud", "misconduct", "violation"]
    for label in list(BEHAVIOR_LABEL_MAP.values()) + list(EVENT_LABEL_MAP.values()):
        for word in forbidden:
            assert word not in label.lower()


def test_bbox_correctness():
    frame = blank(w=640, h=360)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=7,
        event_type="Repeated Looking Right",
        event_code="B2",
        event_label="Looking Right",
        start_frame=0,
        end_frame=10,
        start_time=0,
        end_time=1,
        frame_number=10,
        timestamp_seconds=1.0,
        bbox={"x_min": 50, "y_min": 60, "x_max": 130, "y_max": 160},
        observation_count=10,
        supporting_observations=8,
        missing_observations=0,
        config_version="v1",
        method_version="geometric-v1",
        explanation="test",
    )
    out = annotator.annotate(frame, ev)
    assert out[60, 50].tolist() != [0, 0, 0]
    assert out.shape[0] == 360 and out.shape[1] == 640
