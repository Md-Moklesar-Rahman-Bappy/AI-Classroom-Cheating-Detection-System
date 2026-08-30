import tempfile
from pathlib import Path

import cv2
import numpy as np
import pytest

from app.detection.yolo_detector import UltralyticsDetector
from app.events.repository import InMemoryEventRepository
from app.evidence.manager import EvidenceManager
from app.jobs.models import AnalysisJob, JobStatus, can_transition
from app.jobs.repository import InMemoryJobRepository
from app.jobs.service import RecordedAnalysisService
from app.schemas.models import BoundingBox, DetectionResult


def make_video(path, frames=6, w=64, h=48):
    fourcc = cv2.VideoWriter_fourcc(*"mp4v")
    vw = cv2.VideoWriter(str(path), fourcc, 10, (w, h))
    for _ in range(frames):
        vw.write(np.zeros((h, w, 3), dtype=np.uint8))
    vw.release()


class FakeDetector:
    def __init__(self, detections_per_frame=None, fail=False, fail_load=False):
        self.detections_per_frame = detections_per_frame or []
        self.fail = fail
        self.fail_load = fail_load
        self.calls = 0
        self.checksum = "fake"

    def is_loaded(self):
        return not self.fail_load

    def detect(self, frame):
        if self.fail:
            raise RuntimeError("detector failed")
        self.calls += 1
        idx = self.calls - 1
        if idx < len(self.detections_per_frame):
            return self.detections_per_frame[idx]
        return []


class FailOnceDetector:
    def __init__(self):
        self.calls = 0
        self.checksum = "fake"

    def is_loaded(self):
        return True

    def detect(self, frame):
        self.calls += 1
        if self.calls == 1:
            raise RuntimeError("first call fails")
        return []


def test_valid_e2e(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "valid_e2e.mp4"
    make_video(p, 6)
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev", enabled=True)
    detector = UltralyticsDetector(model_path="yolo11n.pt")
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store", tmp_path / "out"
    )
    job = service.create_job_from_existing(p)
    assert job.status == JobStatus.queued
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    assert job.status == JobStatus.completed
    assert job.output_path is not None
    assert Path(job.output_path).exists()
    assert job.metrics["processed_frame_count"] > 0
    assert job.progress_percent == 100.0


def test_invalid_file(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "no_such.mp4"
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev2", enabled=False)
    detector = FakeDetector()
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store2", tmp_path / "out2"
    )
    with pytest.raises(FileNotFoundError):
        service.create_job_from_existing(p)


def test_empty_file(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "empty_r.mp4"
    p.write_bytes(b"")
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev3", enabled=False)
    detector = FakeDetector()
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store3", tmp_path / "out3"
    )
    with pytest.raises(ValueError):
        service.create_job_from_existing(p)
    if p.exists():
        p.unlink()


def test_invalid_state_transition():
    job = AnalysisJob(input_path="x.mp4", original_filename="x.mp4", status=JobStatus.completed)
    with pytest.raises(ValueError):
        job.transition(JobStatus.processing)
    assert not can_transition(JobStatus.completed, JobStatus.processing)
    assert can_transition(JobStatus.queued, JobStatus.processing)


def test_cancellation(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "cancel.mp4"
    make_video(p, 10)
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_cancel", enabled=False)
    detector = FakeDetector()
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_c", tmp_path / "out_c"
    )
    job = service.create_job_from_existing(p)
    job.cancel_requested = True
    job_repo.update(job)
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    assert job.status == JobStatus.cancelled


def test_retry(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "retry.mp4"
    make_video(p, 3)
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_retry", enabled=False)
    detector = FakeDetector(fail_load=True)
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_r", tmp_path / "out_r"
    )
    job = service.create_job_from_existing(p)
    try:
        service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    except Exception:
        pass
    failed = job_repo.get(job.job_id)
    assert failed.status == JobStatus.failed
    new_job = service.retry(job.job_id)
    assert new_job.status == JobStatus.queued
    assert new_job.job_id != job.job_id
    with pytest.raises(ValueError):
        service.retry(new_job.job_id)


def test_detector_failure_counts(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "det_fail.mp4"
    make_video(p, 3)
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_df", enabled=False)
    detector = FailOnceDetector()
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_df", tmp_path / "out_df"
    )
    job = service.create_job_from_existing(p)
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    assert job.status == JobStatus.completed
    assert job.metrics["error_count"] >= 1


def test_writer_failure(monkeypatch, tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "writer_fail.mp4"
    make_video(p, 3)
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_wf", enabled=False)
    detector = FakeDetector()
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_wf", tmp_path / "out_wf"
    )
    job = service.create_job_from_existing(p)
    monkeypatch.setattr(
        cv2,
        "VideoWriter",
        lambda *a, **k: type(
            "FakeW", (), {"isOpened": lambda s: False, "release": lambda s: None}
        )(),
    )
    with pytest.raises(RuntimeError):
        service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    failed = job_repo.get(job.job_id)
    assert failed.status == JobStatus.failed


def test_evidence_failure(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "ev_fail.mp4"
    make_video(p, 3)
    phone = DetectionResult(
        class_id=67,
        class_name="cell phone",
        confidence=0.9,
        bbox=BoundingBox(x_min=0, y_min=0, x_max=10, y_max=10),
    )
    detector = FakeDetector(detections_per_frame=[[phone], [], []])
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_ef", enabled=True)
    evidence_mgr.save_snapshot = lambda *a, **k: None
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_ef", tmp_path / "out_ef"
    )
    job = service.create_job_from_existing(p)
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    assert job.status == JobStatus.completed
    assert job.event_count == 1


def test_duplicate_suppression_and_cooldown(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "cooldown.mp4"
    make_video(p, 5)
    phone = DetectionResult(
        class_id=67,
        class_name="cell phone",
        confidence=0.9,
        bbox=BoundingBox(x_min=0, y_min=0, x_max=10, y_max=10),
    )
    detector = FakeDetector(detections_per_frame=[[phone], [phone], [phone], [phone], [phone]])
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_cd", enabled=False)
    service = RecordedAnalysisService(
        job_repo,
        event_repo,
        evidence_mgr,
        detector,
        tmp_path / "store_cd",
        tmp_path / "out_cd",
        event_cooldown_frames=3,
    )
    job = service.create_job_from_existing(p)
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    events = event_repo.list_by_job(job.job_id)
    assert len(events) == 2
    assert events[0].frame_number == 0
    assert events[1].frame_number == 3


def test_no_detections(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "no_det.mp4"
    make_video(p, 3)
    detector = FakeDetector(detections_per_frame=[[], [], []])
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_nd", enabled=True)
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_nd", tmp_path / "out_nd"
    )
    job = service.create_job_from_existing(p)
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    assert job.event_count == 0
    assert len(event_repo.list_by_job(job.job_id)) == 0
    assert len(evidence_mgr.list_for_job(job.job_id)) == 0


def test_person_detections_not_phone_event(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "person_only.mp4"
    make_video(p, 2)
    person = DetectionResult(
        class_id=0,
        class_name="person",
        confidence=0.9,
        bbox=BoundingBox(x_min=0, y_min=0, x_max=10, y_max=10),
    )
    detector = FakeDetector(detections_per_frame=[[person], [person]])
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_p", enabled=True)
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_p", tmp_path / "out_p"
    )
    job = service.create_job_from_existing(p)
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    assert job.event_count == 0
    cap = cv2.VideoCapture(job.output_path)
    assert cap.isOpened()
    cap.release()


def test_phone_detections(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "phone.mp4"
    make_video(p, 2)
    phone = DetectionResult(
        class_id=67,
        class_name="cell phone",
        confidence=0.95,
        bbox=BoundingBox(x_min=5, y_min=5, x_max=20, y_max=20),
    )
    detector = FakeDetector(detections_per_frame=[[phone], []])
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_phone", enabled=True)
    service = RecordedAnalysisService(
        job_repo,
        event_repo,
        evidence_mgr,
        detector,
        tmp_path / "store_phone",
        tmp_path / "out_phone",
    )
    job = service.create_job_from_existing(p)
    job = service.process(job.job_id, process_every_n_frames=1, target_width=64, target_height=48)
    events = event_repo.list_by_job(job.job_id)
    assert len(events) == 1
    assert events[0].event_type == "Mobile Phone Detected"
    assert events[0].requires_review is True
    assert Path(job.output_path).exists()
    evidences = evidence_mgr.list_for_job(job.job_id)
    assert len(evidences) == 1


def test_path_traversal(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "traversal.mp4"
    make_video(p, 2)
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_trav", enabled=False)
    detector = FakeDetector()
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_trav", tmp_path / "out_trav"
    )
    with pytest.raises(ValueError):
        service.create_job(tmp_path / "tmp.mp4", "../evil.mp4")
    with pytest.raises(ValueError):
        service.create_job(tmp_path / "tmp.mp4", "a/b.mp4")


def test_upload_validation_rejects_unsupported(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "bad.txt"
    p.write_bytes(b"not video")
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_bad", enabled=False)
    detector = FakeDetector()
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_bad", tmp_path / "out_bad"
    )
    with pytest.raises(ValueError):
        service.create_job(p, "bad.txt")


def test_temporary_cleanup(tmp_path=Path(tempfile.gettempdir())):
    from fastapi.testclient import TestClient

    from app.main import app

    with TestClient(app) as client:
        p = tmp_path / "cleanup.mp4"
        make_video(p, 2)
        with open(p, "rb") as f:
            resp = client.post(
                "/api/v1/jobs/recorded", files={"file": ("cleanup.mp4", f, "video/mp4")}
            )
        assert resp.status_code in (200, 201)
        tmp_files = list(Path(tempfile.gettempdir()).glob("tmp*"))
        leaked = [x for x in tmp_files if x.suffix == ".mp4" and "cleanup" in x.name]
        assert len(leaked) == 0


def test_metrics_correctness(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "metrics.mp4"
    make_video(p, 9, w=64, h=48)
    detector = FakeDetector()
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(tmp_path / "ev_met", enabled=False)
    service = RecordedAnalysisService(
        job_repo, event_repo, evidence_mgr, detector, tmp_path / "store_met", tmp_path / "out_met"
    )
    job = service.create_job_from_existing(p)
    job = service.process(job.job_id, process_every_n_frames=3, target_width=64, target_height=48)
    m = job.metrics
    assert m["source_frame_count"] == 9
    assert m["processed_frame_count"] == 3
    assert m["skipped_frame_count"] == 6
    assert m["detection_invocation_count"] == 3
    assert m["source_fps"] == 10.0
    assert m["processing_duration_seconds"] > 0
    assert m["effective_processing_fps"] > 0
    assert "avg_detection_latency_ms" in m
    assert m["event_count"] == job.event_count
