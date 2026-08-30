import tempfile
from pathlib import Path

import cv2
import numpy as np
from fastapi.testclient import TestClient

from app.main import app


def make_video(path, frames=4):
    fourcc = cv2.VideoWriter_fourcc(*"mp4v")
    vw = cv2.VideoWriter(str(path), fourcc, 10, (64, 48))
    for _ in range(frames):
        vw.write(np.zeros((48, 64, 3), dtype=np.uint8))
    vw.release()


def test_jobs_lifecycle():
    with TestClient(app) as client:
        tmp = Path(tempfile.gettempdir()) / "api_lifecycle.mp4"
        make_video(tmp, 4)
        with open(tmp, "rb") as f:
            resp = client.post(
                "/api/v1/jobs/recorded", files={"file": ("lifecycle.mp4", f, "video/mp4")}
            )
        assert resp.status_code == 200
        data = resp.json()
        job_id = data["job_id"]
        assert data["status"] in ("completed", "failed", "cancelled", "queued", "processing")

        resp = client.get(f"/api/v1/jobs/{job_id}")
        assert resp.status_code == 200
        assert resp.json()["job_id"] == job_id

        resp = client.get(f"/api/v1/jobs/{job_id}/events")
        assert resp.status_code == 200
        assert "total" in resp.json()

        resp = client.get(f"/api/v1/jobs/{job_id}/metrics")
        assert resp.status_code == 200
        assert "metrics" in resp.json()


def test_jobs_invalid_file():
    with TestClient(app) as client:
        tmp = Path(tempfile.gettempdir()) / "api_invalid.txt"
        tmp.write_bytes(b"hello")
        with open(tmp, "rb") as f:
            resp = client.post(
                "/api/v1/jobs/recorded", files={"file": ("invalid.txt", f, "text/plain")}
            )
        assert resp.status_code == 422
        if tmp.exists():
            tmp.unlink()


def test_jobs_empty_file():
    with TestClient(app) as client:
        tmp = Path(tempfile.gettempdir()) / "api_empty.mp4"
        tmp.write_bytes(b"")
        with open(tmp, "rb") as f:
            resp = client.post(
                "/api/v1/jobs/recorded", files={"file": ("empty.mp4", f, "video/mp4")}
            )
        assert resp.status_code == 422
        if tmp.exists():
            tmp.unlink()


def test_jobs_cancel_and_retry():
    with TestClient(app) as client:
        tmp = Path(tempfile.gettempdir()) / "api_cancel.mp4"
        make_video(tmp, 3)
        with open(tmp, "rb") as f:
            resp = client.post(
                "/api/v1/jobs/recorded", files={"file": ("cancel.mp4", f, "video/mp4")}
            )
        job_id = resp.json()["job_id"]
        resp = client.post(f"/api/v1/jobs/{job_id}/cancel")
        assert resp.status_code == 200
        resp = client.post(f"/api/v1/jobs/{job_id}/retry")
        assert resp.status_code in (200, 409, 201)
        if resp.status_code == 409:
            assert "detail" in resp.json()


def test_jobs_not_found():
    with TestClient(app) as client:
        fake_id = "00000000-0000-0000-0000-000000000000"
        assert client.get(f"/api/v1/jobs/{fake_id}").status_code == 404
        assert client.post(f"/api/v1/jobs/{fake_id}/cancel").status_code == 404
        assert client.post(f"/api/v1/jobs/{fake_id}/retry").status_code == 404
        assert client.get(f"/api/v1/jobs/{fake_id}/events").status_code == 404
        assert client.get(f"/api/v1/jobs/{fake_id}/metrics").status_code == 404


def test_upload_path_traversal():
    with TestClient(app) as client:
        tmp = Path(tempfile.gettempdir()) / "api_trav.mp4"
        make_video(tmp, 2)
        with open(tmp, "rb") as f:
            resp = client.post(
                "/api/v1/jobs/recorded", files={"file": ("../evil.mp4", f, "video/mp4")}
            )
        assert resp.status_code == 422
