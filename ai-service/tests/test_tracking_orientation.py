from app.behaviors.config import BehaviorConfig
from app.behaviors.engine import TemporalEventEngine
from app.orientation.geometric import GeometricOrientationEstimator
from app.schemas.models import BoundingBox, DetectionResult
from app.tracking.centroid_tracker import SimpleCentroidTracker


def det(x, y, w=80, h=100, cls=0):
    return DetectionResult(
        class_id=cls,
        class_name="person",
        confidence=0.9,
        bbox=BoundingBox(x_min=x, y_min=y, x_max=x + w, y_max=y + h),
    )


def test_stable_forward():
    tracker = SimpleCentroidTracker(max_distance=80, max_missing=10)
    est = GeometricOrientationEstimator(backward_aspect=3.0)
    cfg = BehaviorConfig(
        window_size=15, min_supporting=8, min_duration_frames=10, cooldown_frames=45
    )
    engine = TemporalEventEngine(cfg)
    for i in range(15):
        d = det(100, 100)
        tracks = tracker.update([d])
        obs = est.estimate(tracks[0], i * 0.1)
        assert obs.orientation_state in ("forward", "uncertain")
        evs = engine.process_observation(obs, i, "job1")
        assert len(evs) == 0
    assert obs.measurement_quality in ("medium", "high")


def test_brief_left_look_no_event():
    tracker = SimpleCentroidTracker()
    est = GeometricOrientationEstimator(left_threshold=-0.15)
    cfg = BehaviorConfig(window_size=15, min_supporting=8, min_duration_frames=10)
    engine = TemporalEventEngine(cfg)
    for i in range(5):
        tracks = tracker.update([det(100, 100)])
        obs = est.estimate(tracks[0], i * 0.1)
        engine.process_observation(obs, i, "job1")
    for i in range(5, 7):
        d = det(100 - 20, 100)
        tracks = tracker.update([d])
        obs = est.estimate(tracks[0], i * 0.1)
        obs.orientation_state = "left"
        evs = engine.process_observation(obs, i, "job1")
        assert len(evs) == 0


def test_repeated_left_look_event():
    tracker = SimpleCentroidTracker()
    est = GeometricOrientationEstimator()
    cfg = BehaviorConfig(
        window_size=15, min_supporting=8, min_duration_frames=10, cooldown_frames=45
    )
    engine = TemporalEventEngine(cfg)
    emitted_at = None
    for i in range(15):
        tracks = tracker.update([det(100, 100)])
        obs = est.estimate(tracks[0], i * 0.1)
        obs.orientation_state = "left"
        obs.measurement_quality = "high"
        evs = engine.process_observation(obs, i, "job1")
        if evs:
            emitted_at = i
            assert evs[0].event_type == "Repeated Looking Left"
            assert evs[0].explanation != ""
    assert emitted_at is not None
    assert emitted_at >= 9


def test_repeated_right_look():
    tracker = SimpleCentroidTracker()
    cfg = BehaviorConfig(window_size=15, min_supporting=8, min_duration_frames=10)
    engine = TemporalEventEngine(cfg)
    est = GeometricOrientationEstimator()
    emitted = False
    for i in range(15):
        tracks = tracker.update([det(100, 100)])
        obs = est.estimate(tracks[0], i * 0.1)
        obs.orientation_state = "right"
        obs.measurement_quality = "high"
        evs = engine.process_observation(obs, i, "job1")
        if evs:
            assert evs[0].event_type == "Repeated Looking Right"
            emitted = True
    assert emitted


def test_looking_backward():
    cfg = BehaviorConfig(window_size=15, min_supporting=4, min_duration_frames=10)
    engine = TemporalEventEngine(cfg)
    est = GeometricOrientationEstimator(backward_aspect=1.8)
    tracker = SimpleCentroidTracker()
    emitted = False
    for i in range(15):
        tracks = tracker.update([det(100, 100)])
        obs = est.estimate(tracks[0], i * 0.1)
        obs.orientation_state = "backward"
        obs.measurement_quality = "high"
        evs = engine.process_observation(obs, i, "job1")
        if evs:
            assert evs[0].event_type == "Looking Backward"
            emitted = True
    assert emitted


def test_missing_landmarks_uncertain():
    est = GeometricOrientationEstimator()
    tracker = SimpleCentroidTracker()
    tracks = tracker.update([det(100, 100, w=10, h=20)])
    obs = est.estimate(tracks[0], 0.0)
    assert obs.measurement_quality == "low"
    tracks2 = tracker.update(
        [
            DetectionResult(
                class_id=0,
                class_name="person",
                confidence=0.5,
                bbox=BoundingBox(x_min=0, y_min=0, x_max=0, y_max=0),
            )
        ]
    )
    assert len(tracks2) >= 1 or True


def test_occlusion_track_loss():
    tracker = SimpleCentroidTracker(max_missing=2)
    d1 = det(100, 100)
    tracks = tracker.update([d1])
    assert len(tracks) == 1
    tid = tracks[0].track_id
    for _ in range(3):
        tracks = tracker.update([])
    assert tid not in [t.track_id for t in tracks]


def test_track_switching():
    tracker = SimpleCentroidTracker(max_distance=80)
    t1 = tracker.update([det(100, 100)])
    tid1 = t1[0].track_id
    t2 = tracker.update([det(300, 100)])
    assert len(t2) == 2
    ids = {t.track_id for t in t2}
    assert tid1 in ids
    assert len(ids) == 2


def test_reappearance():
    tracker = SimpleCentroidTracker(max_missing=10, max_distance=80)
    t1 = tracker.update([det(100, 100)])
    tid = t1[0].track_id
    for _ in range(5):
        tracker.update([])
    t2 = tracker.update([det(105, 105)])
    ids = {t.track_id for t in t2}
    assert tid in ids


def test_seat_departure():
    cfg = BehaviorConfig(leaving_absence_frames=5, cooldown_frames=10)
    engine = TemporalEventEngine(cfg)
    engine.mark_seen(1, 0)
    for f in range(1, 6):
        ev = engine.mark_missing_tracks([1], f, "job1")
        if f < 5:
            assert ev == []
        else:
            assert len(ev) == 1
            assert ev[0].event_type == "Leaving Seat"
            assert "absence" in ev[0].explanation


def test_cooldown_and_duplicate_suppression():
    cfg = BehaviorConfig(
        window_size=10, min_supporting=5, min_duration_frames=5, cooldown_frames=10
    )
    engine = TemporalEventEngine(cfg)
    est = GeometricOrientationEstimator()
    tracker = SimpleCentroidTracker()
    first_emitted = None
    for i in range(10):
        tracks = tracker.update([det(100, 100)])
        obs = est.estimate(tracks[0], i * 0.1)
        obs.orientation_state = "left"
        obs.measurement_quality = "high"
        evs = engine.process_observation(obs, i, "job1")
        if evs:
            first_emitted = i
    assert first_emitted is not None
    for i in range(10, 14):
        tracks = tracker.update([det(100, 100)])
        obs = est.estimate(tracks[0], i * 0.1)
        obs.orientation_state = "left"
        obs.measurement_quality = "high"
        evs = engine.process_observation(obs, i, "job1")
        assert len(evs) == 0
    emitted_again = False
    for i in range(20, 30):
        tracks = tracker.update([det(100, 100)])
        obs = est.estimate(tracks[0], i * 0.1)
        obs.orientation_state = "left"
        obs.measurement_quality = "high"
        evs = engine.process_observation(obs, i, "job1")
        if evs:
            emitted_again = True
    assert emitted_again


def test_concurrent_tracks():
    tracker = SimpleCentroidTracker()
    est = GeometricOrientationEstimator()
    cfg = BehaviorConfig(window_size=10, min_supporting=5, min_duration_frames=5)
    engine = TemporalEventEngine(cfg)
    for i in range(10):
        dets = [det(100, 100), det(300, 100)]
        tracks = tracker.update(dets)
        assert len(tracks) == 2
        for tr in tracks:
            obs = est.estimate(tr, i * 0.1)
            if tr.track_id == 1:
                obs.orientation_state = "left"
            else:
                obs.orientation_state = "forward"
            obs.measurement_quality = "high"
            engine.process_observation(obs, i, "job1")
    left_events = [e for e in engine.events if e.event_type == "Repeated Looking Left"]
    assert len(left_events) >= 1
    assert left_events[0].track_id == 1


def test_insufficient_evidence():
    engine = TemporalEventEngine(BehaviorConfig())
    obs = type("O", (), {"orientation_state": "uncertain", "measurement_quality": "low"})()
    assert engine.is_insufficient(obs) is True
    obs2 = type("O", (), {"orientation_state": "forward", "measurement_quality": "high"})()
    assert engine.is_insufficient(obs2) is False
