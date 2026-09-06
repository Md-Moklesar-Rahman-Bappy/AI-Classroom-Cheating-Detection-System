import numpy as np

from app.behaviors.config import BehaviorConfig
from app.behaviors.engine import TemporalEventEngine
from app.events.rules import MultiplePersonsRule, create_d3_events, create_events_for_detections
from app.events.taxonomy import TAXONOMY
from app.evidence.annotator import COLOR_POLICY
from app.schemas.models import BoundingBox, DetectionResult
from app.tracking.centroid_tracker import SimpleCentroidTracker


def det(x, y, w=80, h=100, cls=0, conf=0.9):
    name = {0: "person", 67: "cell phone"}.get(cls, str(cls))
    return DetectionResult(
        class_id=cls,
        class_name=name,
        confidence=conf,
        bbox=BoundingBox(x_min=x, y_min=y, x_max=x + w, y_max=y + h),
    )


def test_taxonomy_has_11():
    assert len(TAXONOMY) == 11
    assert set(TAXONOMY.keys()) == {
        "D1",
        "D2",
        "D3",
        "B1",
        "B2",
        "B3",
        "B4",
        "B5",
        "S1",
        "S2",
        "S3",
    }


def test_color_policy_11():
    assert COLOR_POLICY["D1"] == (0, 200, 0)
    assert COLOR_POLICY["D2"] == (255, 0, 0)
    assert COLOR_POLICY["D3"] == (0, 255, 255)
    assert COLOR_POLICY["B1"] == (0, 165, 255)
    assert COLOR_POLICY["B2"] == (0, 165, 255)
    assert COLOR_POLICY["B3"] == (0, 165, 255)
    assert COLOR_POLICY["B5"] == (0, 165, 255)
    assert COLOR_POLICY["B4"] == (0, 0, 255)
    assert "S3" in COLOR_POLICY


def test_d3_multiple_persons():
    rule = MultiplePersonsRule(threshold=2, cooldown_frames=5)
    dets = [det(10, 10), det(200, 10)]
    assert rule.should_emit(0, dets, None) is True
    rule.record_emission(0)
    assert rule.should_emit(1, dets, None) is False
    assert rule.should_emit(10, dets, None) is True
    dets_single = [det(10, 10)]
    assert rule.should_emit(10, dets_single, None) is False


def test_d3_creation():
    dets = [det(10, 10), det(200, 10)]
    tracker = SimpleCentroidTracker()
    tracks = tracker.update(dets)
    events = create_d3_events("job1", 5, 0.5, dets, tracks=tracks)
    assert len(events) == 1
    assert events[0].event_code == "D3"
    assert events[0].event_category == "detection"
    assert events[0].track_id is not None
    assert events[0].bbox is not None


def test_d3_with_seat_region():
    region = {"x_min": 0, "y_min": 0, "x_max": 100, "y_max": 100}
    dets = [det(10, 10), det(300, 300)]
    events = create_d3_events("job1", 5, 0.5, dets, seat_region=region)
    assert len(events) == 0
    dets2 = [det(10, 10), det(50, 50)]
    events2 = create_d3_events("job1", 5, 0.5, dets2, seat_region=region)
    assert len(events2) == 1


def test_b5_excessive_head_movement():
    cfg = BehaviorConfig(
        head_movement_switch_threshold=4, head_movement_window=15, cooldown_frames=45
    )
    engine = TemporalEventEngine(cfg)
    tracker = SimpleCentroidTracker()
    emitted = False
    pattern = ["left", "right", "left", "right", "left", "right", "left", "right"]
    for i, state in enumerate(pattern * 2):
        tracks = tracker.update([det(100, 100)])
        from app.orientation.geometric import GeometricOrientationEstimator

        obs = GeometricOrientationEstimator().estimate(tracks[0], i * 0.1)
        obs.orientation_state = state
        obs.measurement_quality = "high"
        obs.supporting_geometry["bbox"] = {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
        evs = engine.process_observation(obs, i, "job1")
        for ev in evs:
            if ev.event_code == "B5":
                emitted = True
                assert ev.event_label == "Excessive Head Movement"
                assert ev.event_category == "behavior"
    assert emitted


def test_b5_no_false_positive_stable():
    cfg = BehaviorConfig(
        head_movement_switch_threshold=4, head_movement_window=15, cooldown_frames=45
    )
    engine = TemporalEventEngine(cfg)
    tracker = SimpleCentroidTracker()
    for i in range(15):
        tracks = tracker.update([det(100, 100)])
        from app.orientation.geometric import GeometricOrientationEstimator

        obs = GeometricOrientationEstimator().estimate(tracks[0], i * 0.1)
        obs.orientation_state = "left"
        obs.measurement_quality = "high"
        obs.supporting_geometry["bbox"] = {"x_min": 100, "y_min": 100, "x_max": 180, "y_max": 200}
        evs = engine.process_observation(obs, i, "job1")
        for ev in evs:
            assert ev.event_code != "B5"


def test_s3_tracking_lost():
    cfg = BehaviorConfig(
        tracking_lost_frames=3, leaving_absence_frames=10, tracking_lost_cooldown=10
    )
    engine = TemporalEventEngine(cfg)
    engine.mark_seen(1, 0, bbox={"x_min": 10, "y_min": 10, "x_max": 50, "y_max": 50}, timestamp=0.0)
    evs = engine.mark_missing_tracks([1], 1, "job1", timestamp=0.1)
    assert len(evs) == 0
    evs = engine.mark_missing_tracks([1], 3, "job1", timestamp=0.3)
    assert len(evs) == 1
    assert evs[0].event_code == "S3"
    assert evs[0].event_category == "system"
    assert evs[0].event_label == "Tracking Lost"
    assert evs[0].bbox is not None


def test_s3_before_b4():
    cfg = BehaviorConfig(
        tracking_lost_frames=5,
        leaving_absence_frames=15,
        tracking_lost_cooldown=30,
        cooldown_frames=45,
    )
    engine = TemporalEventEngine(cfg)
    engine.mark_seen(1, 0, bbox={"x_min": 10, "y_min": 10, "x_max": 50, "y_max": 50}, timestamp=0.0)
    evs = engine.mark_missing_tracks([1], 5, "job1", timestamp=0.5)
    assert any(e.event_code == "S3" for e in evs)
    evs2 = engine.mark_missing_tracks([1], 16, "job1", timestamp=1.6)
    assert any(e.event_code == "B4" for e in evs2) or len(evs2) == 0


def test_s1_s2_taxonomy_exist():
    assert TAXONOMY["S1"]["name"] == "Normal"
    assert TAXONOMY["S2"]["name"] == "Insufficient Evidence"
    assert TAXONOMY["S1"]["category"] == "system"
    assert TAXONOMY["S2"]["category"] == "system"


def test_responsible_ai_labels_v2():
    forbidden = ["cheater", "cheating", "fraud", "misconduct", "violation"]
    for v in TAXONOMY.values():
        for w in forbidden:
            assert w not in v["label"].lower()
            assert w not in v["name"].lower()


def test_evidence_annotator_v2_colors():
    import uuid
    import cv2

    from app.behaviors.models import BehaviorEvent
    from app.evidence.annotator import EvidenceAnnotator

    frame = np.zeros((360, 640, 3), dtype=np.uint8)
    ann = EvidenceAnnotator()
    for code in ["D1", "D2", "D3", "B1", "B2", "B3", "B4", "B5", "S3"]:
        ev = BehaviorEvent(
            event_id=str(uuid.uuid4()),
            job_id="j1",
            track_id=1,
            event_type=code,
            event_code=code,
            event_label=TAXONOMY[code]["label"],
            start_frame=0,
            end_frame=5,
            start_time=0,
            end_time=0.5,
            frame_number=5,
            timestamp_seconds=0.5,
            bbox={"x_min": 10, "y_min": 10, "x_max": 50, "y_max": 50},
            observation_count=5,
            supporting_observations=5,
            missing_observations=0,
            config_version="v2",
            method_version="geometric-v1",
            explanation="test",
        )
        out = ann.annotate(frame, ev)
        assert out.shape == frame.shape


def test_api_event_category_exposed():
    from app.events.models import DetectionEvent

    ev = DetectionEvent.create_multiple_persons(
        "job1", 5, 0.5, {"x_min": 0, "y_min": 0, "x_max": 100, "y_max": 100}, track_ids=[1, 2]
    )
    assert ev.event_category == "detection"
    assert ev.event_code == "D3"
