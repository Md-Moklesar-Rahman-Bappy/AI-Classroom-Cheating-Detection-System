import time
from pathlib import Path

import cv2
from fastapi import FastAPI, HTTPException, Query

from .api.health import router as health_router
from .api.health import set_detector
from .config.settings import settings
from .core.logging import get_logger
from .detection.yolo_detector import UltralyticsDetector
from .inputs.recorded import RecordedVideoInput
from .inputs.scheduler import FrameScheduler
from .metrics.collector import MetricsCollector
from .rendering.renderer import BoundingBoxRenderer

logger = get_logger(__name__)
app = FastAPI(title=settings.app_name, version=settings.app_version, debug=settings.debug)
app.include_router(health_router, prefix="/api/v1")

_detector: UltralyticsDetector | None = None
_start_time = time.time()


@app.on_event("startup")
def startup():
    global _detector
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
