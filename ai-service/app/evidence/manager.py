import hashlib
import time
import uuid
from dataclasses import dataclass
from pathlib import Path

import cv2
import numpy as np


@dataclass
class EvidenceRecord:
    evidence_id: str
    event_id: str
    job_id: str
    frame_number: int
    timestamp_seconds: float
    image_width: int
    image_height: int
    file_checksum: str
    storage_path: str
    created_at: float
    retention_status: str = "active"


class EvidenceManager:
    def __init__(self, base_dir: str | Path, enabled: bool = True):
        self.base_dir = Path(base_dir)
        self.enabled = enabled
        self.base_dir.mkdir(parents=True, exist_ok=True)

    def _checksum_file(self, path: Path) -> str:
        h = hashlib.sha256()
        with open(path, "rb") as f:
            for chunk in iter(lambda: f.read(8192), b""):
                h.update(chunk)
        return h.hexdigest()

    def save_snapshot(
        self,
        frame: np.ndarray,
        job_id: str,
        event_id: str,
        frame_number: int,
        timestamp_seconds: float,
    ) -> EvidenceRecord | None:
        if not self.enabled:
            return None
        try:
            evidence_id = str(uuid.uuid4())
            filename = f"{job_id}_{evidence_id}.jpg"
            job_dir = self.base_dir / job_id
            job_dir.mkdir(parents=True, exist_ok=True)
            storage_path = job_dir / filename
            ok = cv2.imwrite(str(storage_path), frame)
            if not ok:
                return None
            checksum = self._checksum_file(storage_path)
            h, w = frame.shape[0], frame.shape[1]
            return EvidenceRecord(
                evidence_id=evidence_id,
                event_id=event_id,
                job_id=job_id,
                frame_number=frame_number,
                timestamp_seconds=timestamp_seconds,
                image_width=w,
                image_height=h,
                file_checksum=checksum,
                storage_path=str(storage_path),
                created_at=time.time(),
                retention_status="active",
            )
        except Exception:
            return None

    def list_for_job(self, job_id: str) -> list[Path]:
        job_dir = self.base_dir / job_id
        if not job_dir.exists():
            return []
        return list(job_dir.glob("*.jpg"))
