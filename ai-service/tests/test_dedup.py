from app.events.dedup import EventDeduplicator


def test_duplicate_inside_cooldown_suppressed():
    d = EventDeduplicator(cooldown_frames=45, default_cooldown_seconds=5)
    assert not d.should_suppress("job1", "cam1", 1, "B4", 100, 10.0)
    d.record("job1", "cam1", 1, "B4", 100, 10.0)
    assert d.should_suppress("job1", "cam1", 1, "B4", 110, 11.0)
    assert d.suppressed_counts["B4"] == 1


def test_outside_cooldown_created():
    d = EventDeduplicator(cooldown_frames=45)
    d.record("job1", "cam1", 1, "S3", 100, 10.0)
    assert not d.should_suppress("job1", "cam1", 1, "S3", 200, 20.0)


def test_different_track_not_suppressed():
    d = EventDeduplicator(cooldown_frames=45)
    d.record("job1", "cam1", 1, "B4", 100, 10.0)
    assert not d.should_suppress("job1", "cam1", 2, "B4", 110, 11.0)


def test_different_event_type_not_suppressed():
    d = EventDeduplicator(cooldown_frames=45)
    d.record("job1", "cam1", 1, "B4", 100, 10.0)
    assert not d.should_suppress("job1", "cam1", 1, "S3", 110, 11.0)


def test_different_job_not_suppressed():
    d = EventDeduplicator(cooldown_frames=45)
    d.record("job1", "cam1", 1, "B4", 100, 10.0)
    assert not d.should_suppress("job2", "cam1", 1, "B4", 110, 11.0)


def test_deterministic_replay():
    d1 = EventDeduplicator(cooldown_frames=45)
    d2 = EventDeduplicator(cooldown_frames=45)
    seq = [
        (("job1", "cam1", 1, "B4", 100, 10.0)),
        (("job1", "cam1", 1, "B4", 110, 11.0)),
        (("job1", "cam1", 1, "B4", 200, 20.0)),
    ]
    r1 = []
    for args in seq:
        s = d1.should_suppress(*args)
        if not s:
            d1.record(*args)
        r1.append(s)
    r2 = []
    for args in seq:
        s = d2.should_suppress(*args)
        if not s:
            d2.record(*args)
        r2.append(s)
    assert r1 == r2 == [False, True, False]


def test_stale_cleanup():
    d = EventDeduplicator(cooldown_frames=45)
    d.record("job1", "cam1", 1, "B4", 100, 10.0)
    d.cleanup_job("job1")
    assert not d.should_suppress("job1", "cam1", 1, "B4", 110, 11.0)
