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
    track_id: int | None = None
    event_code: str | None = None
    event_label: str | None = None
    bbox: dict | None = None
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
        track_id: int | None = None,
        event_code: str | None = None,
        event_label: str | None = None,
        bbox: dict | None = None,
        tracks: list | None = None,
        detections: list | None = None,
        event_obj=None,
    ) -> EvidenceRecord | None:
        if not self.enabled:
            return None
        try:
            if event_obj is not None:
                from .annotator import EvidenceAnnotator

                annotator = EvidenceAnnotator()
                try:
                    frame = annotator.annotate(
                        frame, event_obj, tracks=tracks, detections=detections
                    )
                except Exception:
                    pass
                if track_id is None:
                    track_id = getattr(event_obj, "track_id", None)
                if event_code is None:
                    event_code = getattr(event_obj, "event_code", None)
                if event_label is None:
                    event_label = getattr(event_obj, "event_label", None) or getattr(
                        event_obj, "event_type", None
                    )
                if bbox is None:
                    bbox = getattr(event_obj, "bbox", None) or getattr(
                        event_obj, "associated_track_bbox", None
                    )
            evidence_id = str(uuid.uuid4())
            safe_track = f"t{track_id}" if track_id is not None else "tX"
            safe_code = event_code or "UN"
            filename = f"job_{job_id}_frame_{int(frame_number):06d}_track_{safe_track}_{safe_code}_{evidence_id}.jpg"
            job_dir = self.base_dir / job_id
            job_dir.mkdir(parents=True, exist_ok=True)
            storage_path = job_dir / filename
            ok = cv2.imwrite(str(storage_path), frame)
            if not ok:
                return None
            checksum = self._checksum_file(storage_path)
            try:
                print(
                    f"[Evidence] frame={frame_number} track={track_id} event={event_code or safe_code} bbox={bbox} hash={checksum[:12]} file={filename}"
                )
            except Exception:
                pass
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
                track_id=track_id,
                event_code=event_code,
                event_label=event_label,
                bbox=dict(bbox) if isinstance(bbox, dict) else None,
                retention_status="active",
            )
        except Exception:
            return None

    def save_annotated_snapshot(
        self,
        frame: np.ndarray,
        job_id: str,
        event_obj,
        tracks: list | None = None,
        detections: list | None = None,
    ) -> EvidenceRecord | None:
        fn = getattr(event_obj, "frame_number", None)
        if fn is None:
            fn = getattr(event_obj, "end_frame", 0)
        ts = getattr(event_obj, "timestamp_seconds", None)
        if ts is None:
            ts = (
                getattr(event_obj, "end_time", 0) or getattr(event_obj, "timestamp_seconds", 0) or 0
            )
        return self.save_snapshot(
            frame,
            job_id,
            getattr(event_obj, "event_id", str(uuid.uuid4())),
            int(fn),
            float(ts),
            event_obj=event_obj,
            tracks=tracks,
            detections=detections,
        )

    def list_for_job(self, job_id: str) -> list[Path]:
        job_dir = self.base_dir / job_id
        if not job_dir.exists():
            return []
        return list(job_dir.glob("*.jpg"))
