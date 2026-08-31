import pathlib
import time

from fastapi.testclient import TestClient

from app.inputs.camera_config import CameraSourceConfig, SourceState
from app.live.session import (
    _bounded_delay,
    _sessions,
    _sessions_lock,
    create_session,
    get_session,
    start_session,
    stop_session,
)
from app.main import app


def test_no_break_in_finally():
    path = pathlib.Path(__file__).parent.parent / "app" / "live" / "session.py"
    text = path.read_text()
    assert "finally:" in text
    lines = text.splitlines()
    in_finally = False
    finally_indent = None
    for i, line in enumerate(lines, 1):
        stripped = line.lstrip()
        indent = len(line) - len(stripped)
        if stripped.startswith("finally:"):
            in_finally = True
            finally_indent = indent
            continue
        if in_finally:
            if line.strip() == "":
                continue
            if indent <= finally_indent and stripped and not stripped.startswith("#"):
                in_finally = False
                continue
            assert not stripped.startswith("break"), f"break in finally at line {i}: {line}"
            if "break" in stripped:
                assert False, f"break found in finally block at line {i}: {line}"


def test_stop_token_respected():
    from unittest.mock import Mock

    config = CameraSourceConfig(source_type="test", identifier="test")
    session = create_session(config)
    # Mock detector
    mock_detector = Mock()
    mock_detector.is_loaded.return_value = True

    def fake_detect(frame):
        return []

    mock_detector.detect = fake_detect
    start_session(session.session_id, mock_detector)
    time.sleep(0.3)
    assert get_session(session.session_id).state in (
        SourceState.monitoring,
        SourceState.connected,
        SourceState.reconnecting,
    )
    stop_session(session.session_id)
    time.sleep(0.2)
    assert get_session(session.session_id).state == SourceState.stopped
    assert get_session(session.session_id).stop_token.is_set()
    # Cleanup
    with _sessions_lock:
        _sessions.pop(session.session_id, None)


def test_reconnect_logic_bounded_delay():
    assert _bounded_delay(1, 1000, 30000) == 1.0
    assert _bounded_delay(2, 1000, 30000) == 2.0
    assert _bounded_delay(3, 1000, 30000) == 4.0
    assert _bounded_delay(6, 1000, 30000) == 30.0
    assert _bounded_delay(10, 1000, 30000) == 30.0
    # Verify reconnect increments and respects max
    config = CameraSourceConfig(
        source_type="test",
        identifier="test",
        reconnect_max_attempts=2,
        reconnect_base_delay_ms=100,
        reconnect_max_delay_ms=1000,
    )
    session = create_session(config)
    from unittest.mock import Mock, patch

    mock_detector = Mock()
    mock_detector.is_loaded.return_value = True
    mock_detector.detect.return_value = []

    # Make source that fails to open to trigger reconnect
    with patch("app.live.session._get_input_source") as mock_get:

        class FailingSource:
            def open(self):
                raise ValueError("fail open")

            def close(self):
                pass

        mock_get.return_value = FailingSource()
        start_session(session.session_id, mock_detector)
        time.sleep(0.6)
        sess = get_session(session.session_id)
        assert sess.metrics.reconnect_count >= 1
        stop_session(session.session_id)
    with _sessions_lock:
        _sessions.pop(session.session_id, None)


def test_resource_cleanup_guaranteed():
    from unittest.mock import Mock, patch

    config = CameraSourceConfig(source_type="test", identifier="test")
    session = create_session(config)
    mock_detector = Mock()
    mock_detector.is_loaded.return_value = True
    mock_detector.detect.return_value = []

    close_called = {"count": 0}

    class TrackingSource:
        def open(self):
            pass

        def frames(self):
            yield from []

        def close(self):
            close_called["count"] += 1

    with patch("app.live.session._get_input_source", return_value=TrackingSource()):
        start_session(session.session_id, mock_detector)
        time.sleep(0.2)
        stop_session(session.session_id)
        time.sleep(0.2)
        assert close_called["count"] >= 1

    with _sessions_lock:
        _sessions.pop(session.session_id, None)


def test_stop_during_reconnect_via_api():
    with TestClient(app) as client:
        # Ensure clean
        from app.live.session import _sessions, _sessions_lock

        with _sessions_lock:
            for sid in list(_sessions.keys()):
                try:
                    client.post(f"/api/v1/live/{sid}/stop")
                except Exception:
                    pass
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        assert resp.status_code == 200
        sid = resp.json()["session_id"]
        # Immediately stop during potential reconnect window
        resp = client.post(f"/api/v1/live/{sid}/stop")
        assert resp.status_code == 200
        # Repeated stop idempotent
        resp = client.post(f"/api/v1/live/{sid}/stop")
        assert resp.status_code == 200
        assert resp.json()["status"] == "stopped"
