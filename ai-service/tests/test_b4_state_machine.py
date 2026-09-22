from app.behaviors.config import BehaviorConfig
from app.behaviors.rules import LeavingSeatRule, TrackingLostRule
from app.behaviors.engine import TemporalEventEngine


def test_leave_once_stay_absent_10s_emits_one_b4():
    cfg = BehaviorConfig(
        leaving_absence_frames=45,
        tracking_lost_frames=15,
        tracking_lost_cooldown=30,
        cooldown_frames=45,
    )
    rule = LeavingSeatRule(cfg)
    track = TrackingLostRule(cfg)
    # seen at 0
    rule.mark_seen(1, 0, {"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100}, 0.0)
    track.mark_seen(1, 0, {"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100}, 0.0)
    b4_events = []
    s3_events = []
    # simulate 10 seconds at 30fps processed every 3 => 10 fps, 10s = 100 frames
    for f in range(1, 100):
        ts = f * 0.1
        s3 = track.mark_missing(1, f, ts)
        if s3:
            s3_events.append(s3)
        b4 = rule.mark_missing(1, f, ts)
        if b4:
            b4_events.append(b4)
    assert len(s3_events) == 1, f"S3 should emit once, got {len(s3_events)}"
    assert len(b4_events) == 1, (
        f"B4 should emit once for continuous absence, got {len(b4_events)} frames {[e.frame_number for e in b4_events]}"
    )
    assert b4_events[0].frame_number >= 45


def test_return_resets_b4():
    cfg = BehaviorConfig(
        leaving_absence_frames=45,
        tracking_lost_frames=15,
        tracking_lost_cooldown=30,
        cooldown_frames=45,
    )
    rule = LeavingSeatRule(cfg)
    track = TrackingLostRule(cfg)
    rule.mark_seen(1, 0, {"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100}, 0.0)
    track.mark_seen(1, 0, {"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100}, 0.0)
    b4_1 = None
    for f in range(1, 60):
        b4 = rule.mark_missing(1, f, f * 0.1)
        track.mark_missing(1, f, f * 0.1)
        if b4:
            b4_1 = b4
            break
    assert b4_1 is not None
    # return at 100
    rule.mark_seen(1, 100, {"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100}, 10.0)
    track.mark_seen(1, 100, {"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100}, 10.0)
    b4_2 = None
    for f in range(101, 160):
        b4 = rule.mark_missing(1, f, f * 0.1)
        track.mark_missing(1, f, f * 0.1)
        if b4:
            b4_2 = b4
            break
    assert b4_2 is not None, "Second leave after return should emit second B4"
    assert b4_2.frame_number >= 100 + 45


def test_no_repeated_b4_while_absent():
    cfg = BehaviorConfig(
        leaving_absence_frames=45,
        tracking_lost_frames=15,
        tracking_lost_cooldown=30,
        cooldown_frames=45,
    )
    engine = TemporalEventEngine(cfg)
    tid = 1
    bbox = {"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100}
    engine.mark_seen(tid, 0, bbox, 0.0)
    events = []
    for f in range(1, 200):
        evs = engine.mark_missing_tracks([tid], f, "job1", timestamp=f * 0.1)
        events.extend(evs)
    b4s = [e for e in events if e.event_code == "B4"]
    s3s = [e for e in events if e.event_code == "S3"]
    assert len(s3s) == 1, f"Expected 1 S3, got {len(s3s)}"
    assert len(b4s) == 1, (
        f"Expected 1 B4 for 200 frames absent, got {len(b4s)}: {[e.frame_number for e in b4s]}"
    )


def test_evidence_single_pair_per_b4_state():
    # Verify that mark_missing does not create multiple TwoFrameEvidence for same state
    cfg = BehaviorConfig(leaving_absence_frames=45)
    rule = LeavingSeatRule(cfg)
    rule.mark_seen(1, 10, {"x_min": 0, "y_min": 0, "x_max": 10, "y_max": 10}, 1.0)
    ev1 = rule.mark_missing(1, 55, 5.5)
    assert ev1 is not None
    assert ev1.two_frame_evidence is not None
    ev2 = rule.mark_missing(1, 60, 6.0)
    assert ev2 is None, "Second call while still absent must not create second evidence"
