import hashlib
import time
import uuid
from pathlib import Path

import cv2
import psutil

from ..core.logging import get_logger
from ..detection.base import ObjectDetector
from ..events.repository import EventRepository
from ..events.rules import MobilePhoneEventRule, create_events_for_detections
from ..evidence.manager import EvidenceManager
from ..inputs.recorded import RecordedVideoInput
from ..inputs.scheduler import FrameScheduler
from ..rendering.renderer import BoundingBoxRenderer
from .models import AnalysisJob, JobStatus
from .repository import JobRepository

logger = get_logger(__name__)

ALLOWED_MIME = {"video/mp4", "video/avi", "video/quicktime", "video/x-msvideo", "video/x-matroska"}
ALLOWED_EXTS = {".mp4", ".avi", ".mov", ".mkv"}


def _safe_filename(original: str) -> str:
    suffix = Path(original).suffix.lower()
    if suffix not in ALLOWED_EXTS:
        suffix = ".mp4"
    return f"{uuid.uuid4().hex}{suffix}"


def _checksum_file(path: Path) -> str:
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            h.update(chunk)
    return h.hexdigest()


class RecordedAnalysisService:
    def __init__(
        self,
        job_repo: JobRepository,
        event_repo: EventRepository,
        evidence_manager: EvidenceManager,
        detector: ObjectDetector,
        storage_dir: str | Path,
        output_dir: str | Path,
        max_upload_mb: int = 500,
        event_cooldown_frames: int = 30,
    ):
        self.job_repo = job_repo
        self.event_repo = event_repo
        self.evidence_manager = evidence_manager
        self.detector = detector
        self.storage_dir = Path(storage_dir)
        self.output_dir = Path(output_dir)
        self.storage_dir.mkdir(parents=True, exist_ok=True)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.max_upload_mb = max_upload_mb
        self.event_cooldown_frames = event_cooldown_frames

    def _validate_upload(self, temp_path: Path, original_filename: str) -> None:
        ext = Path(original_filename).suffix.lower()
        if ext and ext not in ALLOWED_EXTS:
            raise ValueError(f"Unsupported file type: {ext}")
        size_mb = temp_path.stat().st_size / (1024 * 1024)
        if size_mb > self.max_upload_mb:
            raise ValueError(f"File too large: {size_mb:.1f}MB > {self.max_upload_mb}MB")
        if temp_path.stat().st_size == 0:
            raise ValueError("Empty file")
        cap = cv2.VideoCapture(str(temp_path))
        try:
            if not cap.isOpened():
                raise ValueError("Unreadable video content")
            w = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
            h = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
            if w == 0 or h == 0:
                raise ValueError("Invalid video dimensions")
            ok, frame = cap.read()
            if not ok or frame is None:
                raise ValueError("No readable frames")
        finally:
            cap.release()

    def create_job(self, temp_path: str | Path, original_filename: str) -> AnalysisJob:
        temp_path = Path(temp_path)
        if ".." in original_filename or "/" in original_filename or "\\" in original_filename:
            raise ValueError("Invalid filename")
        self._validate_upload(temp_path, original_filename)
        safe_name = _safe_filename(original_filename)
        stored_path = self.storage_dir / safe_name
        stored_path.write_bytes(temp_path.read_bytes())
        job = AnalysisJob(
            input_path=str(stored_path),
            original_filename=Path(original_filename).name,
            status=JobStatus.pending,
        )
        job.transition(JobStatus.queued)
        self.job_repo.create(job)
        logger.info(f"Created job {job.job_id} for {original_filename}")
        return job

    def create_job_from_existing(self, existing_path: str | Path) -> AnalysisJob:
        p = Path(existing_path)
        if not p.exists():
            raise FileNotFoundError(f"Video not found: {p}")
        if ".." in str(p):
            raise ValueError("Path traversal detected")
        self._validate_upload(p, p.name)
        job = AnalysisJob(
            input_path=str(p.resolve()),
            original_filename=p.name,
            status=JobStatus.pending,
        )
        job.transition(JobStatus.queued)
        self.job_repo.create(job)
        return job

    def cancel(self, job_id: str) -> AnalysisJob:
        job = self.job_repo.get(job_id)
        if job is None:
            raise KeyError(f"Job not found: {job_id}")
        job.request_cancel()
        self.job_repo.update(job)
        return job

    def retry(self, job_id: str) -> AnalysisJob:
        job = self.job_repo.get(job_id)
        if job is None:
            raise KeyError(f"Job not found: {job_id}")
        if job.status not in (JobStatus.failed, JobStatus.cancelled):
            raise ValueError(f"Cannot retry job in status {job.status}")
        new_job = AnalysisJob(
            input_path=job.input_path,
            original_filename=job.original_filename,
            status=JobStatus.pending,
        )
        new_job.transition(JobStatus.queued)
        self.job_repo.create(new_job)
        return new_job

    def process(
        self,
        job_id: str,
        process_every_n_frames: int = 3,
        target_width: int = 640,
        target_height: int = 360,
        evidence_enabled: bool = True,
    ) -> AnalysisJob:
        job = self.job_repo.get(job_id)
        if job is None:
            raise KeyError(f"Job not found: {job_id}")
        if job.status not in (JobStatus.queued, JobStatus.pending):
            raise ValueError(f"Job not ready for processing: {job.status}")
        job.transition(JobStatus.processing)
        self.job_repo.update(job)

        src = RecordedVideoInput(job.input_path)
        sched = FrameScheduler(process_every_n_frames, target_width, target_height)
        renderer = BoundingBoxRenderer()
        rule = MobilePhoneEventRule(cooldown_frames=self.event_cooldown_frames)
        writer = None
        t_start = time.time()
        peak_mem = 0
        error_count = 0
        total_frames = -1
        processed = 0
        skipped = 0
        invocations = 0
        latencies: list[float] = []
        evidence_records: list = []

        try:
            if not self.detector.is_loaded():
                raise RuntimeError("Model not loaded")

            src.open()
            meta = src.metadata()
            job.source_fps = meta.fps
            job.frames_total = meta.frame_count
            total_frames = meta.frame_count

            output_filename = f"{Path(job.input_path).stem}_annotated.mp4"
            output_path = self.output_dir / output_filename
            if output_path.resolve() == Path(job.input_path).resolve():
                output_path = self.output_dir / f"{uuid.uuid4().hex}_annotated.mp4"
            fourcc = cv2.VideoWriter_fourcc(*"mp4v")
            writer = cv2.VideoWriter(
                str(output_path), fourcc, meta.fps or 10, (target_width, target_height)
            )
            if not writer.isOpened():
                raise RuntimeError("Cannot open output writer")

            for packet in src.frames():
                if job.cancel_requested:
                    job.transition(JobStatus.cancelling)
                    self.job_repo.update(job)
                    job.transition(JobStatus.cancelled)
                    self.job_repo.update(job)
                    break

                is_scheduled = sched.should_process(packet.frame_index)
                if not is_scheduled:
                    skipped += 1
                    continue

                frame_proc = sched.preprocess(packet.frame)
                t0 = time.time()
                try:
                    dets = self.detector.detect(frame_proc)
                except Exception as e:
                    error_count += 1
                    logger.error(f"detection failed frame {packet.frame_index}: {e}")
                    continue
                latency = (time.time() - t0) * 1000
                latencies.append(latency)
                invocations += 1
                processed += 1

                job.frames_processed = processed
                job.frames_skipped = skipped
                job.detection_invocations = invocations
                if total_frames > 0:
                    job.progress_percent = min(100.0, (packet.frame_index + 1) / total_frames * 100)
                else:
                    job.progress_percent = 0

                phones = rule.should_emit(packet.frame_index, dets)
                if phones:
                    events = create_events_for_detections(
                        job.job_id, packet.frame_index, packet.timestamp_seconds, phones
                    )
                    for ev in events:
                        self.event_repo.add(ev)
                        job.event_count += 1
                        if evidence_enabled:
                            rec = self.evidence_manager.save_snapshot(
                                frame_proc,
                                job.job_id,
                                ev.event_id,
                                packet.frame_index,
                                packet.timestamp_seconds,
                            )
                            if rec:
                                evidence_records.append(rec)
                            else:
                                error_count += 1
                    rule.record_emission(packet.frame_index)

                annotated = renderer.render(frame_proc, dets)
                writer.write(annotated)

                try:
                    peak_mem = max(peak_mem, psutil.Process().memory_info().rss // 1024 // 1024)
                except Exception:
                    pass

                if processed % 10 == 0:
                    self.job_repo.update(job)

            if job.status not in (JobStatus.cancelled, JobStatus.cancelling):
                job.output_path = str(output_path) if writer else None
                job.output_metadata = {
                    "path": str(output_path) if writer else None,
                    "width": target_width,
                    "height": target_height,
                    "fps": meta.fps,
                    "processed_frames": processed,
                    "checksum": _checksum_file(output_path) if output_path.exists() else None,
                }
                duration = max(0.001, time.time() - t_start)
                job.metrics = {
                    "source_frame_count": total_frames,
                    "processed_frame_count": processed,
                    "skipped_frame_count": skipped,
                    "detection_invocation_count": invocations,
                    "source_fps": meta.fps,
                    "processing_duration_seconds": duration,
                    "effective_processing_fps": processed / duration,
                    "avg_detection_latency_ms": sum(latencies) / len(latencies) if latencies else 0,
                    "peak_memory_mb": peak_mem,
                    "error_count": error_count,
                    "event_count": job.event_count,
                }
                job.progress_percent = 100.0
                job.transition(JobStatus.completed)
                self.job_repo.update(job)

        except Exception as e:
            job.failure_reason = str(e)
            job.error_count = error_count + 1
            try:
                job.transition(JobStatus.failed)
            except ValueError:
                job.status = JobStatus.failed
                job.finished_at = time.time()
            self.job_repo.update(job)
            logger.error(f"job {job_id} failed: {e}")
            if writer is not None:
                try:
                    writer.release()
                except Exception:
                    pass
                try:
                    if output_path and output_path.exists():
                        pass
                except Exception:
                    pass
            raise
        finally:
            try:
                src.close()
            except Exception:
                pass
            if writer is not None:
                try:
                    writer.release()
                except Exception:
                    pass

        return job
