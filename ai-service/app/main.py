import time
from pathlib import Path

import cv2
from fastapi import FastAPI, HTTPException, Query

from .api.health import router as health_router
from .api.health import set_detector
from .api.jobs import router as jobs_router
from .api.jobs import set_service as set_jobs_service
from .behaviors.config import BehaviorConfig
from .config.settings import settings
from .core.logging import get_logger
from .detection.yolo_detector import UltralyticsDetector
from .events.repository import InMemoryEventRepository
from .evidence.manager import EvidenceManager
from .inputs.recorded import RecordedVideoInput
from .inputs.scheduler import FrameScheduler
from .jobs.repository import InMemoryJobRepository
from .jobs.service import RecordedAnalysisService
from .metrics.collector import MetricsCollector
from .rendering.renderer import BoundingBoxRenderer

logger = get_logger(__name__)
app = FastAPI(title=settings.app_name, version=settings.app_version, debug=settings.debug)
app.include_router(health_router, prefix="/api/v1")
app.include_router(jobs_router, prefix="/api/v1")

_detector: UltralyticsDetector | None = None
_service: RecordedAnalysisService | None = None
_start_time = time.time()


@app.on_event("startup")
def startup():
    global _detector, _service
    try:
        _detector = UltralyticsDetector(
            model_path=settings.model_path,
            conf=settings.model_conf_threshold,
            iou=settings.model_iou_threshold,
            imgsz=settings.model_image_size,
            allowed_classes=settings.allowed_classes,
        )
        set_detector(_detector)
        logger.info("Detector loaded")
    except Exception as e:
        logger.error(f"Detector failed to load: {e}")
        _detector = None
        set_detector(None)
    try:
        job_repo = InMemoryJobRepository()
        event_repo = InMemoryEventRepository()
        evidence_mgr = EvidenceManager(settings.evidence_dir, enabled=settings.enable_evidence)
        if _detector is not None and _detector.is_loaded():
            behavior_config = BehaviorConfig(
                window_size=settings.behavior_window_size,
                min_supporting=settings.behavior_min_supporting,
                max_missing=settings.behavior_max_missing,
                min_duration_frames=settings.behavior_min_duration,
                cooldown_frames=settings.behavior_cooldown_frames,
                leaving_absence_frames=settings.behavior_leaving_absence,
                config_version=settings.behavior_config_version,
            )
            _service = RecordedAnalysisService(
                job_repo=job_repo,
                event_repo=event_repo,
                evidence_manager=evidence_mgr,
                detector=_detector,
                storage_dir=settings.storage_dir,
                output_dir=settings.output_dir,
                max_upload_mb=settings.max_upload_mb,
                event_cooldown_frames=settings.event_cooldown_frames,
                behavior_config=behavior_config,
                tracking_max_distance=settings.tracking_max_distance,
                tracking_max_missing=settings.tracking_max_missing,
                orientation_left_threshold=settings.orientation_left_threshold,
                orientation_right_threshold=settings.orientation_right_threshold,
                orientation_backward_aspect=settings.orientation_backward_aspect,
                orientation_method_version=settings.orientation_method_version,
            )
            set_jobs_service(_service)
            logger.info("Recorded analysis service ready")
        else:
            logger.warning("Detector not loaded, job service disabled")
    except Exception as e:
        logger.error(f"Failed to init job service: {e}")


@app.get("/")
def root():
    return {"name": settings.app_name, "version": settings.app_version, "status": "ok"}


@app.post("/api/v1/debug/analyze-local")
def debug_analyze_local(
    path: str = Query(..., description="Local file path (dev only)"),
):
    if not settings.is_development():
        raise HTTPException(status_code=403, detail="Debug endpoint disabled outside development")
    allowed_roots = [Path.cwd() / "ai-service", Path.cwd() / "samples"]
    p = Path(path).resolve()
    if not any(str(p).startswith(str(r.resolve())) for r in allowed_roots if r.exists()):
        if not p.exists():
            raise HTTPException(status_code=404, detail="File not found")
        if str(p).startswith(str(Path.cwd().resolve())) is False:
            raise HTTPException(status_code=403, detail="Path not allowed")
    if _detector is None or not _detector.is_loaded():
        raise HTTPException(status_code=503, detail="Model not loaded")
    src = RecordedVideoInput(str(p))
    sched = FrameScheduler(
        settings.process_every_n_frames, settings.input_width, settings.input_height
    )
    renderer = BoundingBoxRenderer()
    metrics = MetricsCollector()
    out_path = Path(settings.output_dir) / f"debug_{p.stem}_annotated.mp4"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    writer = None
    detections_total = 0
    try:
        src.open()
        meta = src.metadata()
        fourcc = cv2.VideoWriter_fourcc(*"mp4v")
        writer = cv2.VideoWriter(
            str(out_path),
            fourcc,
            meta.fps or 10,
            (settings.input_width, settings.input_height),
        )
        if not writer.isOpened():
            raise RuntimeError("Cannot open writer")
        for packet in sched.filter(src.frames()):
            dets = _detector.detect(packet.frame)
            detections_total += len(dets)
            annotated = renderer.render(packet.frame, dets)
            writer.write(annotated)
            metrics.tick(True)
        return {
            "output": str(out_path),
            "detections": detections_total,
            "metrics": metrics.snapshot(),
        }
    except Exception as e:
        logger.error(f"debug analyze failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        try:
            src.close()
        except Exception:
            pass
        if writer is not None:
            writer.release()
