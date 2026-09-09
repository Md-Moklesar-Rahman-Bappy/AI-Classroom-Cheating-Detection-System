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
    bbox_format: str = "xyxy"
    original_frame_size: dict | None = None
    rendered_frame_size: dict | None = None
    last_valid_detection_frame: int | None = None
    absence_frames: int | None = None
    trigger_frame_number: int = 0
    trigger_timestamp: float = 0.0
    last_detection_frame_number: int = 0
    last_detection_timestamp: float = 0.0
    last_detection_bbox: dict | None = None
    two_frame_evidence: object | None = None
    presence_valid_detection_frame: int | None = None
    absence_source_frames: list | None = None


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
        render_mode: str = "trigger",
    ) -> EvidenceRecord | None:
        if not self.enabled:
            return None
        try:
            original_size = {"width": int(frame.shape[1]), "height": int(frame.shape[0])}
            if event_obj is not None:
                from .annotator import EvidenceAnnotator, _validate_bbox

                annotator = EvidenceAnnotator()
                ev_code_tmp = getattr(event_obj, "event_code", None)
                b_tmp = getattr(event_obj, "bbox", None) or getattr(
                    event_obj, "associated_track_bbox", None
                )
                if (
                    ev_code_tmp in ("S3", "B4")
                    and _validate_bbox(b_tmp, original_size["width"], original_size["height"])
                    is None
                ):
                    return None
                try:
                    frame = annotator.annotate(
                        frame,
                        event_obj,
                        tracks=tracks,
                        detections=detections,
                        render_mode=render_mode,
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
                    f"[Evidence] frame={frame_number} track={track_id} event={event_code or safe_code} render_mode={render_mode} bbox={bbox} hash={checksum[:12]} file={filename}"
                )
            except Exception:
                pass
            h, w = frame.shape[0], frame.shape[1]
            two_frame = getattr(event_obj, "two_frame_evidence", None)
            last_valid = getattr(event_obj, "start_frame", None) if event_obj is not None else None
            absence = (
                getattr(event_obj, "observation_count", None) if event_obj is not None else None
            )
            if absence is None:
                absence = (
                    getattr(event_obj, "missing_observations", None)
                    if event_obj is not None
                    else None
                )
            trigger_frame_number = getattr(event_obj, "trigger_frame_number", None) or frame_number
            trigger_timestamp = getattr(event_obj, "trigger_timestamp", None) or timestamp_seconds
            last_detection_frame_number = (
                getattr(event_obj, "last_detection_frame_number", None) or last_valid
            )
            last_detection_timestamp = getattr(event_obj, "last_detection_timestamp", None) or 0
            last_detection_bbox = getattr(event_obj, "last_detection_bbox", None) or bbox
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
                bbox_format="xyxy",
                original_frame_size=original_size,
                rendered_frame_size={"width": w, "height": h},
                last_valid_detection_frame=last_valid,
                absence_frames=absence,
                trigger_frame_number=trigger_frame_number,
                trigger_timestamp=trigger_timestamp,
                last_detection_frame_number=last_detection_frame_number,
                last_detection_timestamp=last_detection_timestamp,
                last_detection_bbox=dict(last_detection_bbox)
                if isinstance(last_detection_bbox, dict)
                else None,
                two_frame_evidence=two_frame,
            )
        except Exception:
            return None

    def save_two_frame_evidence(
        self,
        trigger_frame: np.ndarray,
        last_detected_frame: np.ndarray,
        job_id: str,
        event_obj,
        tracks: list | None = None,
        detections: list | None = None,
    ) -> tuple[EvidenceRecord | None, EvidenceRecord | None]:
        """Save two-frame evidence: trigger frame and last-detected frame.

        Returns (trigger_record, last_detected_record).
        """
        if not self.enabled:
            return None, None
        trigger_record = self.save_snapshot(
            trigger_frame,
            job_id,
            getattr(event_obj, "event_id", str(uuid.uuid4())),
            int(getattr(event_obj, "frame_number", getattr(event_obj, "trigger_frame_number", 0))),
            float(
                getattr(event_obj, "trigger_timestamp", getattr(event_obj, "timestamp_seconds", 0))
            ),
            track_id=getattr(event_obj, "track_id", None),
            event_code=getattr(event_obj, "event_code", None),
            event_label=getattr(event_obj, "event_label", None),
            tracks=tracks,
            detections=detections,
            event_obj=event_obj,
            render_mode="trigger",
        )
        two_frame = getattr(event_obj, "two_frame_evidence", None)
        if trigger_record and two_frame is not None:
            last_det_frame = np.zeros((360, 640, 3), dtype=np.uint8)
            last_det_frame_number = two_frame.last_detection_frame_number
            last_det_timestamp = two_frame.last_detection_timestamp
            last_det_bbox = two_frame.last_detection_bbox
            if last_det_bbox:
                last_det_event = type(
                    "TempEvent",
                    (),
                    {
                        "event_code": getattr(event_obj, "event_code", None),
                        "track_id": getattr(event_obj, "track_id", None),
                        "bbox": last_det_bbox,
                        "event_type": getattr(event_obj, "event_type", "Unknown"),
                        "frame_number": last_det_frame_number,
                        "timestamp_seconds": last_det_timestamp,
                    },
                )()
                last_det_frame = last_det_frame.copy()
                try:
                    from .annotator import EvidenceAnnotator

                    annotator = EvidenceAnnotator()
                    last_det_frame = annotator.annotate(
                        last_det_frame,
                        last_det_event,
                        tracks=tracks,
                        detections=detections,
                        render_mode="last_detected",
                    )
                except Exception:
                    pass
            last_det_record = self.save_snapshot(
                last_det_frame,
                job_id,
                getattr(event_obj, "event_id", str(uuid.uuid4())),
                int(last_det_frame_number),
                float(last_det_timestamp),
                track_id=getattr(event_obj, "track_id", None),
                event_code=getattr(event_obj, "event_code", None),
                event_label=getattr(event_obj, "event_label", None),
                tracks=tracks,
                detections=detections,
                event_obj=event_obj,
                render_mode="last_detected",
            )
        else:
            last_det_record = None
        return trigger_record, last_det_record

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

    def list_records_for_job(self, job_id: str) -> list[EvidenceRecord]:
        job_dir = self.base_dir / job_id
        if not job_dir.exists():
            return []
        records: list[EvidenceRecord] = []
        for p in sorted(job_dir.glob("*.jpg")):
            try:
                checksum = self._checksum_file(p)
                name = p.name
                parts = name.split("_")
                ev_id = name.split("_")[-1].replace(".jpg", "")
                frame_num = 0
                track = None
                code = None
                for i, part in enumerate(parts):
                    if part == "frame" and i + 1 < len(parts):
                        try:
                            frame_num = int(parts[i + 1])
                        except Exception:
                            pass
                    if part == "track" and i + 1 < len(parts):
                        t = parts[i + 1]
                        if t.startswith("t"):
                            try:
                                track = int(t[1:])
                            except Exception:
                                track = None
                        elif t == "tX":
                            track = None
                if len(parts) >= 2:
                    code = (
                        parts[-2]
                        if parts[-2]
                        in ("D1", "D2", "D3", "B1", "B2", "B3", "B4", "B5", "S1", "S2", "S3")
                        else None
                    )
                records.append(
                    EvidenceRecord(
                        evidence_id=ev_id,
                        event_id=ev_id,
                        job_id=job_id,
                        frame_number=frame_num,
                        timestamp_seconds=0,
                        image_width=640,
                        image_height=360,
                        file_checksum=checksum,
                        storage_path=str(p),
                        created_at=p.stat().st_mtime,
                        track_id=track,
                        event_code=code,
                        event_label=code,
                        bbox=None,
                    )
                )
            except Exception:
                continue
        return records
