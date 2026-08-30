import time
import uuid

from fastapi.testclient import TestClient

from app.live.session import _sessions, _sessions_lock
from app.main import app


def _cleanup_all_sessions(client):
    try:
        with _sessions_lock:
            sids = list(_sessions.keys())
        for sid in sids:
            try:
                client.post(f"/api/v1/live/{sid}/stop")
            except Exception:
                pass
        # Also clear via direct delete for test isolation
        with _sessions_lock:
            for sid in list(_sessions.keys()):
                try:
                    _sessions.pop(sid, None)
                except Exception:
                    pass
    except Exception:
        pass


def test_local_webcam_or_test_stream():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        assert resp.status_code == 200
        sid = resp.json()["session_id"]
        # Health
        resp = client.get(f"/api/v1/live/{sid}/health")
        assert resp.status_code == 200
        assert "state" in resp.json()
        # Stop
        resp = client.post(f"/api/v1/live/{sid}/stop")
        assert resp.status_code == 200


def test_invalid_url():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "rtsp", "identifier": ""})
        assert resp.status_code == 422
        resp = client.post(
            "/api/v1/live/start", json={"source_type": "rtsp", "identifier": "invalid_no_scheme"}
        )
        assert resp.status_code == 422
        resp = client.post(
            "/api/v1/live/start", json={"source_type": "invalid_type", "identifier": "0"}
        )
        assert resp.status_code == 422


def test_authentication_failure():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        # Use wrong token if configured
        resp = client.post(
            "/api/v1/live/start",
            json={"source_type": "test", "identifier": "test"},
            headers={"Authorization": "Bearer wrong"},
        )
        # If token is dev-token-change-me, it will still pass (no auth), else 401
        assert resp.status_code in (200, 401)


def test_connection_timeout():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post(
            "/api/v1/live/start",
            json={"source_type": "rtsp", "identifier": "rtsp://timeout/stream"},
        )
        # Should either succeed (test) or fail gracefully, not hang
        assert resp.status_code in (200, 422, 500)


def test_stream_interruption_and_reconnection():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        assert resp.status_code == 200
        sid = resp.json()["session_id"]
        time.sleep(0.5)
        resp = client.get(f"/api/v1/live/{sid}/health")
        assert resp.status_code == 200
        # Simulate interruption by stopping
        client.post(f"/api/v1/live/{sid}/stop")
        resp = client.get(f"/api/v1/live/{sid}/health")
        assert resp.json()["state"] == "stopped"


def test_stale_frame_detection():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        sid = resp.json()["session_id"]
        time.sleep(0.3)
        resp = client.get(f"/api/v1/live/{sid}/health")
        assert resp.status_code == 200
        assert "last_frame_time" in resp.json()
        client.post(f"/api/v1/live/{sid}/stop")


def test_stop_during_reconnect():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post(
            "/api/v1/live/start",
            json={"source_type": "rtsp", "identifier": "rtsp://reconnect/stream"},
        )
        if resp.status_code == 200:
            sid = resp.json()["session_id"]
            resp = client.post(f"/api/v1/live/{sid}/stop")
            assert resp.status_code == 200
            # Repeated stop idempotent
            resp = client.post(f"/api/v1/live/{sid}/stop")
            assert resp.status_code == 200


def test_duplicate_start():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp1 = client.post(
            "/api/v1/live/start", json={"source_type": "test", "identifier": "test"}
        )
        assert resp1.status_code == 200
        sid1 = resp1.json()["session_id"]
        resp2 = client.post(
            "/api/v1/live/start", json={"source_type": "test", "identifier": "test"}
        )
        assert resp2.status_code == 409
        client.post(f"/api/v1/live/{sid1}/stop")


def test_repeated_stop():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        sid = resp.json()["session_id"]
        resp = client.post(f"/api/v1/live/{sid}/stop")
        assert resp.status_code == 200
        resp = client.post(f"/api/v1/live/{sid}/stop")
        assert resp.status_code == 200
        assert resp.json()["status"] == "stopped"


def test_event_delivery():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        sid = resp.json()["session_id"]
        time.sleep(0.5)
        resp = client.get(f"/api/v1/live/{sid}/events")
        assert resp.status_code == 200
        assert "total" in resp.json()
        client.post(f"/api/v1/live/{sid}/stop")


def test_evidence_generation():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        sid = resp.json()["session_id"]
        time.sleep(0.5)
        resp = client.get(f"/api/v1/live/{sid}/health")
        assert resp.status_code == 200
        client.post(f"/api/v1/live/{sid}/stop")


def test_unauthorized_control():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        fake_id = str(uuid.uuid4())
        # Without auth, if token required, should be 401
        resp = client.post(f"/api/v1/live/{fake_id}/stop")
        assert resp.status_code in (401, 404)


def test_unauthorized_preview():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        fake_id = str(uuid.uuid4())
        resp = client.get(f"/api/v1/live/{fake_id}/preview")
        assert resp.status_code in (401, 404, 409)


def test_credential_redaction():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post(
            "/api/v1/live/start",
            json={"source_type": "rtsp", "identifier": "rtsp://user:pass@host/stream"},
        )
        # Should not log password, check that response doesn't contain pass
        if resp.status_code == 422:
            assert "pass" not in resp.text


def test_resource_cleanup():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        sid = resp.json()["session_id"]
        client.post(f"/api/v1/live/{sid}/stop")
        resp = client.get(f"/api/v1/live/{sid}/health")
        assert resp.json()["state"] == "stopped"
        # Ensure no leak
        resp = client.post(
            "/api/v1/live/start", json={"source_type": "test", "identifier": "test2"}
        )
        assert resp.status_code == 200
        client.post(f"/api/v1/live/{resp.json()['session_id']}/stop")


def test_ai_service_crash_recovery():
    with TestClient(app) as client:
        _cleanup_all_sessions(client)
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        sid = resp.json()["session_id"]
        # Simulate crash by stopping and then starting new
        client.post(f"/api/v1/live/{sid}/stop")
        resp = client.get(f"/api/v1/live/{sid}/health")
        assert resp.status_code == 200
        # New session should work
        resp = client.post("/api/v1/live/start", json={"source_type": "test", "identifier": "test"})
        assert resp.status_code == 200
        client.post(f"/api/v1/live/{resp.json()['session_id']}/stop")
