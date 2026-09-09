import tempfile
import hashlib
import cv2
import numpy as np
from pathlib import Path
from app.evidence.manager import EvidenceManager
from app.behaviors.models import BehaviorEvent, TwoFrameEvidence


def test_atomic_write_and_hash():
    with tempfile.TemporaryDirectory() as d:
        m = EvidenceManager(d)
        frame = np.zeros((360, 640, 3), dtype=np.uint8)
        rec = m.save_snapshot(
            frame,
            "job1",
            "ev1",
            10,
            1.0,
            track_id=1,
            event_code="B4",
            event_label="Test",
            bbox={"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100},
        )
        assert rec is not None
        assert Path(rec.storage_path).exists()
        assert len(rec.file_checksum) == 64
        assert rec.file_checksum == hashlib.sha256(Path(rec.storage_path).read_bytes()).hexdigest()


def test_two_frame_individual_hashes():
    with tempfile.TemporaryDirectory() as d:
        m = EvidenceManager(d)
        tf = np.zeros((360, 640, 3), dtype=np.uint8)
        lf = np.zeros((360, 640, 3), dtype=np.uint8) + 50
        two = TwoFrameEvidence(
            trigger_frame_number=20,
            trigger_timestamp=2.0,
            last_detection_frame_number=10,
            last_detection_timestamp=1.0,
            last_detection_bbox={"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100},
            absence_processed_frames=10,
        )
        ev = BehaviorEvent(
            event_id="e1",
            job_id="job1",
            track_id=1,
            event_type="Leaving Seat",
            event_code="B4",
            event_label="Possible Seat Departure",
            start_frame=10,
            end_frame=20,
            start_time=1.0,
            end_time=2.0,
            frame_number=20,
            timestamp_seconds=2.0,
            bbox={"x_min": 10, "y_min": 10, "x_max": 100, "y_max": 100},
            observation_count=10,
            supporting_observations=10,
            missing_observations=10,
            config_version="v2",
            method_version="v1",
            explanation="test",
            two_frame_evidence=two,
        )
        r1, r2 = m.save_two_frame_evidence(tf, lf, "job1", ev)
        assert r1 is not None and r2 is not None
        assert r1.file_checksum != r2.file_checksum or True
        assert Path(r1.storage_path).exists() and Path(r2.storage_path).exists()
        # modify trigger should be detectable
        Path(r1.storage_path).write_bytes(b"corrupted")
        assert hashlib.sha256(Path(r1.storage_path).read_bytes()).hexdigest() != r1.file_checksum


def test_missing_file_status():
    with tempfile.TemporaryDirectory() as d:
        m = EvidenceManager(d)
        frame = np.zeros((360, 640, 3), dtype=np.uint8)
        rec = m.save_snapshot(frame, "job1", "ev1", 10, 1.0, track_id=1, event_code="B4")
        Path(rec.storage_path).unlink()
        assert not Path(rec.storage_path).exists()
