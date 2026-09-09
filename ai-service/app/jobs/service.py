import hashlib
import time
import uuid
from pathlib import Path

import cv2
import psutil

from ..behaviors.config import BehaviorConfig
from ..behaviors.engine import TemporalEventEngine
from ..core.logging import get_logger
from ..detection.base import ObjectDetector
from ..events.dedup import EventDeduplicator
from ..events.repository import EventRepository
from ..events.rules import (
    MobilePhoneEventRule,
    MultiplePersonsRule,
    create_d3_events,
    create_events_for_detections,
)
from ..evidence.manager import EvidenceManager
from ..inputs.recorded import RecordedVideoInput
from ..inputs.scheduler import FrameScheduler
from ..orientation.geometric import GeometricOrientationEstimator
from ..rendering.renderer import BoundingBoxRenderer
from ..tracking.centroid_tracker import SimpleCentroidTracker
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
        behavior_config: BehaviorConfig | None = None,
        tracking_max_distance: float = 80.0,
        tracking_max_missing: int = 10,
        orientation_left_threshold: float = -0.15,
        orientation_right_threshold: float = 0.15,
        orientation_backward_aspect: float = 1.8,
        orientation_method_version: str = "geometric-v1",
        multiple_persons_threshold: int = 2,
        multiple_persons_iou_threshold: float = 0.3,
        head_movement_switch_threshold: int = 4,
        head_movement_window: int = 15,
        tracking_lost_frames: int = 10,
    ):
        self.job_repo = job_repo
        self.multiple_persons_threshold = multiple_persons_threshold
        self.multiple_persons_iou_threshold = multiple_persons_iou_threshold
        self.head_movement_switch_threshold = head_movement_switch_threshold
        self.head_movement_window = head_movement_window
        self.tracking_lost_frames = tracking_lost_frames
        self.event_repo = event_repo
        self.evidence_manager = evidence_manager
        self.detector = detector
        self.storage_dir = Path(storage_dir)
        self.output_dir = Path(output_dir)
        self.storage_dir.mkdir(parents=True, exist_ok=True)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.max_upload_mb = max_upload_mb
        self.event_cooldown_frames = event_cooldown_frames
        self.behavior_config = behavior_config or BehaviorConfig()
        self.tracking_max_distance = tracking_max_distance
        self.tracking_max_missing = tracking_max_missing
        self.orientation_left_threshold = orientation_left_threshold
        self.orientation_right_threshold = orientation_right_threshold
        self.orientation_backward_aspect = orientation_backward_aspect
        self.orientation_method_version = orientation_method_version
        self.behavior_events: dict[str, list] = {}
        self.evidence_records: dict[str, list] = {}
        self.deduplicator = EventDeduplicator(
            cooldown_frames=self.behavior_config.cooldown_frames or 45,
            default_cooldown_seconds=5.0,
        )
        self.suppressed_duplicates: dict[str, int] = {}

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

    def get_behavior_events(self, job_id: str) -> list:
        return self.behavior_events.get(job_id, [])

    def get_evidence_records(self, job_id: str) -> list:
        return self.evidence_records.get(job_id, [])

    def get_evidence_for_job(self, job_id: str) -> list:
        recs = self.evidence_records.get(job_id)
        if recs is not None:
            return recs
        try:
            return self.evidence_manager.list_records_for_job(job_id)
        except Exception:
            return []

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
        from ..config.settings import settings as svc_settings

        rule = MobilePhoneEventRule(
            cooldown_frames=self.event_cooldown_frames,
            conf_threshold=svc_settings.phone_conf_threshold,
            min_width=svc_settings.phone_min_width,
            min_height=svc_settings.phone_min_height,
            min_area=svc_settings.phone_min_area,
            aspect_min=svc_settings.phone_aspect_min,
            aspect_max=svc_settings.phone_aspect_max,
        )
        d3_rule = MultiplePersonsRule(
            threshold=self.multiple_persons_threshold,
            iou_threshold=self.multiple_persons_iou_threshold,
            cooldown_frames=self.event_cooldown_frames,
        )
        tracker = SimpleCentroidTracker(
            max_distance=self.tracking_max_distance, max_missing=self.tracking_max_missing
        )
        orientation_estimator = GeometricOrientationEstimator(
            left_threshold=self.orientation_left_threshold,
            right_threshold=self.orientation_right_threshold,
            backward_aspect=self.orientation_backward_aspect,
            method_version=self.orientation_method_version,
        )
        temporal_engine = TemporalEventEngine(self.behavior_config)
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
        behavior_events_for_job: list = []
        track_count = 0

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

            all_track_ids: set[int] = set()
            frame_buffer: dict[int, any] = {}
            frame_buffer_limit = 60
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
                try:
                    if len(frame_buffer) >= frame_buffer_limit:
                        oldest = min(frame_buffer.keys())
                        del frame_buffer[oldest]
                    frame_buffer[packet.frame_index] = frame_proc.copy()
                except Exception:
                    pass
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

                tracker._current_frame = packet.frame_index
                tracks = tracker.update(dets)
                for tr in tracks:
                    all_track_ids.add(tr.track_id)
                track_count = len(all_track_ids)

                observations = []
                track_bbox_map: dict[int, dict] = {}
                for tr in tracks:
                    obs = orientation_estimator.estimate(tr, packet.timestamp_seconds)
                    try:
                        tb = tr.bbox.bbox
                        bbox_dict = {
                            "x_min": float(tb.x_min),
                            "y_min": float(tb.y_min),
                            "x_max": float(tb.x_max),
                            "y_max": float(tb.y_max),
                        }
                        obs.supporting_geometry["bbox"] = bbox_dict
                        track_bbox_map[tr.track_id] = bbox_dict
                    except Exception:
                        pass
                    observations.append(obs)

                current_tids = {tr.track_id for tr in tracks}
                for obs in observations:
                    bbox_for_track = track_bbox_map.get(obs.track_id)
                    temporal_engine.mark_seen(
                        obs.track_id,
                        packet.frame_index,
                        bbox=bbox_for_track,
                        timestamp=packet.timestamp_seconds,
                    )
                    evs = temporal_engine.process_observation(obs, packet.frame_index, job.job_id)
                    for ev in evs:
                        if ev.bbox is None and bbox_for_track is not None:
                            ev.bbox = dict(bbox_for_track)
                            ev.frame_number = packet.frame_index
                            ev.timestamp_seconds = packet.timestamp_seconds
                        src = str(job.id) if hasattr(job, "id") else job.job_id
                        if self.deduplicator.should_suppress(
                            job.job_id,
                            src,
                            ev.track_id,
                            ev.event_code,
                            packet.frame_index,
                            packet.timestamp_seconds,
                        ):
                            self.suppressed_duplicates[ev.event_code] = (
                                self.suppressed_duplicates.get(ev.event_code, 0) + 1
                            )
                            continue
                        self.deduplicator.record(
                            job.job_id,
                            src,
                            ev.track_id,
                            ev.event_code,
                            packet.frame_index,
                            packet.timestamp_seconds,
                        )
                        behavior_events_for_job.append(ev)
                        job.event_count += 1
                        if evidence_enabled:
                            ev_code = getattr(ev, "event_code", None)
                            if (
                                ev_code in ("S3", "B4")
                                and hasattr(ev, "two_frame_evidence")
                                and ev.two_frame_evidence is not None
                            ):
                                last_fn = getattr(
                                    ev.two_frame_evidence, "last_detection_frame_number", None
                                )
                                last_frame_cached = (
                                    frame_buffer.get(last_fn) if last_fn is not None else None
                                )
                                rec_trigger, rec_last = (
                                    self.evidence_manager.save_two_frame_evidence(
                                        frame_proc,
                                        last_frame_cached,
                                        job.job_id,
                                        ev,
                                        tracks=tracks,
                                        detections=dets,
                                    )
                                )
                                if rec_trigger:
                                    evidence_records.append(rec_trigger)
                                if rec_last:
                                    evidence_records.append(rec_last)
                            else:
                                rec = self.evidence_manager.save_snapshot(
                                    frame_proc,
                                    job.job_id,
                                    ev.event_id,
                                    packet.frame_index,
                                    packet.timestamp_seconds,
                                    event_obj=ev,
                                    tracks=tracks,
                                    detections=dets,
                                )
                                if rec:
                                    evidence_records.append(rec)

                missing_tids = []
                for tid in list(temporal_engine.leaving_rule.last_seen.keys()):
                    if tid not in current_tids:
                        missing_tids.append(tid)
                if missing_tids:
                    leaving_evs = temporal_engine.mark_missing_tracks(
                        missing_tids,
                        packet.frame_index,
                        job.job_id,
                        timestamp=packet.timestamp_seconds,
                    )
                    for ev in leaving_evs:
                        src = str(job.id) if hasattr(job, "id") else job.job_id
                        code = getattr(ev, "event_code", "S3")
                        if self.deduplicator.should_suppress(
                            job.job_id,
                            src,
                            ev.track_id,
                            code,
                            packet.frame_index,
                            packet.timestamp_seconds,
                        ):
                            self.suppressed_duplicates[code] = (
                                self.suppressed_duplicates.get(code, 0) + 1
                            )
                            continue
                        self.deduplicator.record(
                            job.job_id,
                            src,
                            ev.track_id,
                            code,
                            packet.frame_index,
                            packet.timestamp_seconds,
                        )
                        behavior_events_for_job.append(ev)
                        job.event_count += 1
                        if evidence_enabled:
                            ev_code = getattr(ev, "event_code", None)
                            if (
                                ev_code in ("S3", "B4")
                                and hasattr(ev, "two_frame_evidence")
                                and ev.two_frame_evidence is not None
                            ):
                                last_fn = getattr(
                                    ev.two_frame_evidence, "last_detection_frame_number", None
                                )
                                last_frame_cached = (
                                    frame_buffer.get(last_fn) if last_fn is not None else None
                                )
                                rec_trigger, rec_last = (
                                    self.evidence_manager.save_two_frame_evidence(
                                        frame_proc,
                                        last_frame_cached,
                                        job.job_id,
                                        ev,
                                        tracks=tracks,
                                        detections=dets,
                                    )
                                )
                                if rec_trigger:
                                    evidence_records.append(rec_trigger)
                                if rec_last:
                                    evidence_records.append(rec_last)
                            else:
                                rec = self.evidence_manager.save_snapshot(
                                    frame_proc,
                                    job.job_id,
                                    ev.event_id,
                                    packet.frame_index,
                                    packet.timestamp_seconds,
                                    event_obj=ev,
                                    tracks=tracks,
                                    detections=dets,
                                )
                                if rec:
                                    evidence_records.append(rec)

                phones = rule.should_emit(packet.frame_index, dets)
                if phones:
                    events = create_events_for_detections(
                        job.job_id,
                        packet.frame_index,
                        packet.timestamp_seconds,
                        phones,
                        tracks=tracks,
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
                                event_obj=ev,
                                tracks=tracks,
                                detections=dets,
                            )
                            if rec:
                                evidence_records.append(rec)
                            else:
                                error_count += 1
                    rule.record_emission(packet.frame_index)

                if d3_rule.should_emit(packet.frame_index, dets, tracks):
                    d3_events = create_d3_events(
                        job.job_id,
                        packet.frame_index,
                        packet.timestamp_seconds,
                        dets,
                        tracks=tracks,
                        seat_region=self.behavior_config.seat_region,
                    )
                    for ev in d3_events:
                        self.event_repo.add(ev)
                        job.event_count += 1
                        if evidence_enabled:
                            rec = self.evidence_manager.save_snapshot(
                                frame_proc,
                                job.job_id,
                                ev.event_id,
                                packet.frame_index,
                                packet.timestamp_seconds,
                                event_obj=ev,
                                tracks=tracks,
                                detections=dets,
                            )
                            if rec:
                                evidence_records.append(rec)
                    d3_rule.record_emission(packet.frame_index)

                behavior_for_frame = [
                    ev for ev in behavior_events_for_job if ev.end_frame == packet.frame_index
                ]
                annotated = renderer.render(
                    frame_proc, dets, tracks, observations, behavior_for_frame
                )
                writer.write(annotated)

                try:
                    peak_mem = max(peak_mem, psutil.Process().memory_info().rss // 1024 // 1024)
                except Exception:
                    pass

                if processed % 10 == 0:
                    self.job_repo.update(job)

            self.behavior_events[job_id] = behavior_events_for_job
            self.evidence_records[job_id] = evidence_records
            self.deduplicator.cleanup_job(job_id)

            if job.status not in (JobStatus.cancelled, JobStatus.cancelling):
                job.output_path = str(output_path) if writer else None
                job.output_metadata = {
                    "path": str(output_path) if writer else None,
                    "width": target_width,
                    "height": target_height,
                    "fps": meta.fps,
                    "processed_frames": processed,
                    "checksum": _checksum_file(output_path) if output_path.exists() else None,
                    "config_version": self.behavior_config.config_version,
                    "method_version": self.orientation_method_version,
                    "behavior_config": {
                        "window_size": self.behavior_config.window_size,
                        "min_supporting": self.behavior_config.min_supporting,
                        "max_missing": self.behavior_config.max_missing,
                        "min_duration_frames": self.behavior_config.min_duration_frames,
                        "cooldown_frames": self.behavior_config.cooldown_frames,
                        "leaving_absence_frames": self.behavior_config.leaving_absence_frames,
                        "multiple_persons_threshold": self.behavior_config.multiple_persons_threshold,
                        "head_movement_switch_threshold": self.behavior_config.head_movement_switch_threshold,
                        "head_movement_window": self.behavior_config.head_movement_window,
                        "tracking_lost_frames": self.behavior_config.tracking_lost_frames,
                    },
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
                    "track_count": track_count,
                    "behavior_event_count": len(behavior_events_for_job),
                    "orientation_method": self.orientation_method_version,
                    "config_version": self.behavior_config.config_version,
                    "suppressed_duplicate_count": sum(self.suppressed_duplicates.values()),
                    "suppressed_by_type": dict(self.suppressed_duplicates),
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
