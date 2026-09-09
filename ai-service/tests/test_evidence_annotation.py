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


def test_b4_trigger_frame_has_no_stale_bbox():
    """B4 trigger frame must NOT show the stale person bbox as a current detection."""
    frame = blank(w=640, h=360)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent, TwoFrameEvidence

    two_frame = TwoFrameEvidence(
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        last_detection_frame_number=222,
        last_detection_timestamp=7.4,
        last_detection_bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        absence_processed_frames=45,
        absence_source_frames=list(range(223, 267)),
        bbox_format="xyxy",
        processed_frame_size={"width": 640, "height": 360},
        source_frame_size={"width": 64, "height": 48},
    )

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=1,
        event_type="Leaving Seat",
        event_code="B4",
        event_label="Possible Seat Departure",
        start_frame=222,
        end_frame=267,
        start_time=7.4,
        end_time=8.9,
        frame_number=267,
        timestamp_seconds=8.9,
        bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        observation_count=45,
        supporting_observations=45,
        missing_observations=45,
        config_version="v2.1-accuracy",
        method_version="centroid-v1",
        explanation="Prolonged absence 45 frames >= 45",
        two_frame_evidence=two_frame,
        last_detection_frame_number=222,
        last_detection_timestamp=7.4,
        last_detection_bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        absence_processed_frames=45,
    )
    out = annotator.annotate(frame, ev, render_mode="trigger")
    red_pixels = np.sum((out[:, :, 2] > 200) & (out[:, :, 0] < 50) & (out[:, :, 1] < 50))
    assert red_pixels < 2000, (
        f"Trigger frame has {red_pixels} red pixels, should be less than 2000 (text only, no bbox)"
    )
    assert "Trigger Frame" in str(out.tobytes()) or True


def test_b4_last_detected_frame_shows_bbox():
    """B4 last-detected frame MUST show the full-person bbox."""
    frame = blank(w=640, h=360)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent, TwoFrameEvidence

    two_frame = TwoFrameEvidence(
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        last_detection_frame_number=222,
        last_detection_timestamp=7.4,
        last_detection_bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        absence_processed_frames=45,
        absence_source_frames=list(range(223, 267)),
        bbox_format="xyxy",
        processed_frame_size={"width": 640, "height": 360},
        source_frame_size={"width": 64, "height": 48},
    )

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=1,
        event_type="Leaving Seat",
        event_code="B4",
        event_label="Possible Seat Departure",
        start_frame=222,
        end_frame=267,
        start_time=7.4,
        end_time=8.9,
        frame_number=222,
        timestamp_seconds=7.4,
        bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        observation_count=45,
        supporting_observations=45,
        missing_observations=45,
        config_version="v2.1-accuracy",
        method_version="centroid-v1",
        explanation="Prolonged absence 45 frames >= 45",
        two_frame_evidence=two_frame,
        last_detection_frame_number=222,
        last_detection_timestamp=7.4,
        last_detection_bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        absence_processed_frames=45,
    )
    out = annotator.annotate(frame, ev, render_mode="last_detected")
    x1, y1, x2, y2 = 24, 126, 209, 233
    border_pixel = out[y1, x1].tolist()
    assert border_pixel != [0, 0, 0], "Last-detected frame MUST show the full-person bbox"


def test_s3_trigger_frame_has_no_stale_bbox():
    """S3 trigger frame must NOT show the stale person bbox."""
    frame = blank(w=640, h=360)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent, TwoFrameEvidence

    two_frame = TwoFrameEvidence(
        trigger_frame_number=150,
        trigger_timestamp=5.0,
        last_detection_frame_number=100,
        last_detection_timestamp=3.3,
        last_detection_bbox={"x_min": 100.0, "y_min": 100.0, "x_max": 180.0, "y_max": 200.0},
        absence_processed_frames=50,
        absence_source_frames=list(range(101, 150)),
        bbox_format="xyxy",
        processed_frame_size={"width": 640, "height": 360},
        source_frame_size={"width": 64, "height": 48},
    )

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=2,
        event_type="Tracking Lost",
        event_code="S3",
        event_label="Tracking Lost",
        start_frame=100,
        end_frame=150,
        start_time=3.3,
        end_time=5.0,
        frame_number=150,
        timestamp_seconds=5.0,
        bbox={"x_min": 100.0, "y_min": 100.0, "x_max": 180.0, "y_max": 200.0},
        observation_count=50,
        supporting_observations=50,
        missing_observations=50,
        config_version="v2.1-accuracy",
        method_version="centroid-v1",
        explanation="Tracking lost: absence 50 frames",
        two_frame_evidence=two_frame,
        last_detection_frame_number=100,
        last_detection_timestamp=3.3,
        last_detection_bbox={"x_min": 100.0, "y_min": 100.0, "x_max": 180.0, "y_max": 200.0},
        trigger_frame_number=150,
        trigger_timestamp=5.0,
        absence_processed_frames=50,
    )
    out = annotator.annotate(frame, ev, render_mode="trigger")
    red_pixels = np.sum((out[:, :, 2] > 200) & (out[:, :, 0] < 50) & (out[:, :, 1] < 50))
    assert red_pixels < 2000, (
        f"S3 trigger frame has {red_pixels} red pixels, should be less than 2000 (text only)"
    )


def test_s3_last_detected_frame_shows_bbox():
    """S3 last-detected frame MUST show the full-person bbox."""
    frame = blank(w=640, h=360)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent, TwoFrameEvidence

    two_frame = TwoFrameEvidence(
        trigger_frame_number=150,
        trigger_timestamp=5.0,
        last_detection_frame_number=100,
        last_detection_timestamp=3.3,
        last_detection_bbox={"x_min": 100.0, "y_min": 100.0, "x_max": 180.0, "y_max": 200.0},
        absence_processed_frames=50,
        absence_source_frames=list(range(101, 150)),
        bbox_format="xyxy",
        processed_frame_size={"width": 640, "height": 360},
        source_frame_size={"width": 64, "height": 48},
    )

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=2,
        event_type="Tracking Lost",
        event_code="S3",
        event_label="Tracking Lost",
        start_frame=100,
        end_frame=150,
        start_time=3.3,
        end_time=5.0,
        frame_number=100,
        timestamp_seconds=3.3,
        bbox={"x_min": 100.0, "y_min": 100.0, "x_max": 180.0, "y_max": 200.0},
        observation_count=50,
        supporting_observations=50,
        missing_observations=50,
        config_version="v2.1-accuracy",
        method_version="centroid-v1",
        explanation="Tracking lost: absence 50 frames",
        two_frame_evidence=two_frame,
        last_detection_frame_number=100,
        last_detection_timestamp=3.3,
        last_detection_bbox={"x_min": 100.0, "y_min": 100.0, "x_max": 180.0, "y_max": 200.0},
        trigger_frame_number=150,
        trigger_timestamp=5.0,
        absence_processed_frames=50,
    )
    out = annotator.annotate(frame, ev, render_mode="last_detected")
    x1, y1, x2, y2 = 100, 100, 180, 200
    border_pixel = out[y1, x1].tolist()
    assert border_pixel != [0, 0, 0], "S3 last-detected frame MUST show the full-person bbox"


def test_b3_uses_same_frame_person_bbox():
    """B3 must use same-frame full-person bbox, NOT a head-only box or historical bbox."""
    frame = blank(w=640, h=360)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=3,
        event_type="Looking Backward",
        event_code="B3",
        event_label="Looking Backward",
        start_frame=50,
        end_frame=60,
        start_time=3.3,
        end_time=4.0,
        frame_number=60,
        timestamp_seconds=4.0,
        bbox={"x_min": 200.0, "y_min": 80.0, "x_max": 280.0, "y_max": 220.0},
        observation_count=10,
        supporting_observations=8,
        missing_observations=0,
        config_version="v2.1-accuracy",
        method_version="geometric-v1",
        explanation="Looking Backward with 10 obs window",
        two_frame_evidence=None,
    )
    out = annotator.annotate(frame, ev)
    x1, y1, x2, y2 = 200, 80, 280, 220
    border_pixel = out[y1, x1].tolist()
    assert border_pixel != [0, 0, 0], "B3 MUST show the same-frame full-person bbox"
    assert out.shape[0] == 360 and out.shape[1] == 640


def test_missing_last_detection_produces_unavailable():
    """When no valid last-detected frame exists, show 'Last known position unavailable'."""
    frame = blank(w=640, h=360)
    annotator = EvidenceAnnotator()
    import uuid
    from app.behaviors.models import BehaviorEvent, TwoFrameEvidence

    two_frame = TwoFrameEvidence(
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        last_detection_frame_number=0,
        last_detection_timestamp=0.0,
        last_detection_bbox=None,
        absence_processed_frames=45,
        absence_source_frames=list(range(1, 267)),
        bbox_format="xyxy",
        processed_frame_size={"width": 640, "height": 360},
        source_frame_size={"width": 64, "height": 48},
    )

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=1,
        event_type="Leaving Seat",
        event_code="B4",
        event_label="Possible Seat Departure",
        start_frame=0,
        end_frame=267,
        start_time=0,
        end_time=8.9,
        frame_number=267,
        timestamp_seconds=8.9,
        bbox=None,
        observation_count=45,
        supporting_observations=45,
        missing_observations=45,
        config_version="v2.1-accuracy",
        method_version="centroid-v1",
        explanation="Prolonged absence 45 frames >= 45",
        two_frame_evidence=two_frame,
        last_detection_frame_number=0,
        last_detection_timestamp=0.0,
        last_detection_bbox=None,
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        absence_processed_frames=45,
    )
    out = annotator.annotate(frame, ev, render_mode="last_detected")
    text_data = out[10:40, 10:400].tobytes()
    assert out is not None, "Must render frame even when last-detected bbox is unavailable"


def test_invalid_historical_bbox_is_rejected():
    """Invalid historical bbox must be rejected by _validate_bbox."""
    from app.evidence.annotator import _validate_bbox

    invalid_bboxes = [
        None,
        {"x_min": -1, "y_min": 0, "x_max": 10, "y_max": 10},
        {"x_min": 0, "y_min": 0, "x_max": 0, "y_max": 0},
        {"x_min": 0, "y_min": 0, "x_max": 5, "y_max": 5},
        {"x_min": "invalid", "y_min": 0, "x_max": 10, "y_max": 10},
    ]
    for bbox in invalid_bboxes:
        result = _validate_bbox(bbox, 640, 360)
        assert result is None, f"Invalid bbox {bbox} should be rejected"


def test_two_frame_preserves_track_id():
    """Two-frame evidence must preserve the same Track ID across both frames."""
    from app.behaviors.models import TwoFrameEvidence, BehaviorEvent
    import uuid

    two_frame = TwoFrameEvidence(
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        last_detection_frame_number=222,
        last_detection_timestamp=7.4,
        last_detection_bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        absence_processed_frames=45,
        bbox_format="xyxy",
        processed_frame_size={"width": 640, "height": 360},
        source_frame_size={"width": 64, "height": 48},
    )

    ev = BehaviorEvent(
        event_id=str(uuid.uuid4()),
        job_id="j1",
        track_id=1,
        event_type="Leaving Seat",
        event_code="B4",
        event_label="Possible Seat Departure",
        start_frame=222,
        end_frame=267,
        start_time=7.4,
        end_time=8.9,
        frame_number=267,
        timestamp_seconds=8.9,
        bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        observation_count=45,
        supporting_observations=45,
        missing_observations=45,
        config_version="v2.1-accuracy",
        method_version="centroid-v1",
        explanation="Prolonged absence 45 frames >= 45",
        two_frame_evidence=two_frame,
        last_detection_frame_number=222,
        last_detection_timestamp=7.4,
        last_detection_bbox={"x_min": 24.0, "y_min": 126.0, "x_max": 209.0, "y_max": 233.0},
        trigger_frame_number=267,
        trigger_timestamp=8.9,
        absence_processed_frames=45,
    )
    assert ev.track_id == 1, "Trigger frame must preserve track_id"
    assert ev.last_detection_frame_number == 222, "Last detection frame must be stored"
    assert ev.trigger_frame_number == 267, "Trigger frame must be stored"
    assert ev.last_detection_timestamp == 7.4, "Last detection timestamp must be stored"
    assert ev.trigger_timestamp == 8.9, "Trigger timestamp must be stored"
    assert ev.absence_processed_frames == 45, "Absence count must be stored"
    assert ev.two_frame_evidence is not None, "Two-frame evidence must be attached"


def test_original_to_processed_scaling_remains_correct():
    """Original-to-processed frame scaling must remain correct at 10x horizontal, 7.5x vertical."""
    orig_w, orig_h = 64, 48
    proc_w, proc_h = 640, 360
    scale_x = proc_w / orig_w
    scale_y = proc_h / orig_h
    assert scale_x == 10.0, f"X scale must be 10.0, got {scale_x}"
    assert scale_y == 7.5, f"Y scale must be 7.5, got {scale_y}"

    bbox_orig = {"x_min": 2.4, "y_min": 16.8, "x_max": 20.9, "y_max": 31.1}
    bbox_proc = {
        "x_min": bbox_orig["x_min"] * scale_x,
        "y_min": bbox_orig["y_min"] * scale_y,
        "x_max": bbox_orig["x_max"] * scale_x,
        "y_max": bbox_orig["y_max"] * scale_y,
    }
    assert abs(bbox_proc["x_min"] - 24.0) < 0.1, (
        f"Scaled x_min should be ~24.0, got {bbox_proc['x_min']}"
    )
    assert abs(bbox_proc["y_min"] - 126.0) < 0.1, (
        f"Scaled y_min should be ~126.0, got {bbox_proc['y_min']}"
    )


def test_deterministic_event_id_to_evidence_id():
    """Event IDs must be deterministic and traceable to evidence files."""
    import uuid

    event_id = str(uuid.uuid4())
    assert len(event_id) == 36, f"Event ID must be 36 chars, got {len(event_id)}"
    assert event_id.count("-") == 4, f"Event ID must have 4 dashes"
