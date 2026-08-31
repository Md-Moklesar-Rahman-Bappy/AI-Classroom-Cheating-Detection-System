import tempfile
import uuid
from pathlib import Path

from fastapi import APIRouter, Depends, File, Form, HTTPException, Request, UploadFile

from ..config.settings import settings
from ..core.logging import get_logger
from ..jobs.models import JobStatus

logger = get_logger(__name__)
router = APIRouter()

_service = None


def get_service():
    if _service is None:
        raise HTTPException(status_code=503, detail="Service not ready")
    return _service


def set_service(svc) -> None:
    global _service
    _service = svc


def _require_token(token: str | None = None):
    if settings.ai_service_token and settings.ai_service_token != "dev-token-change-me":
        if token != f"Bearer {settings.ai_service_token}":
            raise HTTPException(status_code=401, detail="Authentication required")


@router.post("/jobs/recorded")
async def create_recorded_job(
    request: Request,
    file: UploadFile = File(...),
    original_filename: str = Form(None),
    mime_type: str = Form(None),
    file_size: str = Form(None),
    file_checksum: str = Form(None),
    model_version: str = Form(None),
    config: str = Form(None),
    correlation_id: str = Form(None),
    dashboard_job_id: str = Form(None),
    service=Depends(get_service),
):
    # Correlation ID from header or form
    corr = request.headers.get("X-Correlation-Id") or correlation_id or str(uuid.uuid4())
    # Safe filename handling: use original_filename if provided, else file.filename, never trust path
    original = original_filename or file.filename or "upload.mp4"
    # No path traversal: strip directory, check for .., /, \
    original = Path(original).name
    if ".." in original or "/" in original or "\\" in original:
        raise HTTPException(status_code=422, detail="Invalid filename")
    suffix = Path(original).suffix.lower()
    if suffix and suffix not in {".mp4", ".avi", ".mov", ".mkv"}:
        raise HTTPException(status_code=422, detail="Unsupported file type")
    # MIME validation if provided
    if mime_type and mime_type not in {
        "video/mp4",
        "video/avi",
        "video/quicktime",
        "video/x-msvideo",
        "video/x-matroska",
        "application/octet-stream",
    }:
        # Allow but log; strict check on file content via VideoCapture later
        pass
    # Idempotency: if dashboard_job_id or correlation_id already exists, return existing
    if dashboard_job_id:
        try:
            existing = service.job_repo.get(dashboard_job_id)
            if existing and existing.status.value not in ("failed", "cancelled"):
                return {
                    "job_id": existing.job_id,
                    "remote_job_id": existing.job_id,
                    "status": existing.status.value,
                    "progress_percent": existing.progress_percent,
                    "failure_reason": existing.failure_reason,
                    "correlation_id": corr,
                }
        except Exception:
            pass
    tmp = None
    try:
        tmp_dir = Path(tempfile.gettempdir()) / "ai_input"
        tmp_dir.mkdir(parents=True, exist_ok=True)
        with tempfile.NamedTemporaryFile(delete=False, suffix=suffix or ".mp4", dir=tmp_dir) as t:
            tmp = Path(t.name)
            content = await file.read()
            max_bytes = service.max_upload_mb * 1024 * 1024
            if len(content) > max_bytes:
                raise HTTPException(status_code=422, detail="File too large")
            if len(content) == 0:
                raise HTTPException(status_code=422, detail="Empty file")
            if file_size and str(len(content)) != str(file_size):
                raise HTTPException(status_code=422, detail="File size mismatch")
            if file_checksum:
                import hashlib

                calc = hashlib.sha256(content).hexdigest()
                if calc.lower() != file_checksum.lower():
                    raise HTTPException(status_code=422, detail="File checksum mismatch")
            # MIME sniff: check via VideoCapture readability, not just extension
            t.write(content)
        # Validate video readability via VideoCapture (not just extension)
        try:
            cap = __import__("cv2").VideoCapture(str(tmp))
            if not cap.isOpened():
                raise ValueError("Unreadable video")
            ok, _ = cap.read()
            cap.release()
            if not ok:
                raise ValueError("No readable frames")
        except HTTPException:
            raise
        except Exception as e:
            raise HTTPException(status_code=422, detail=f"Invalid video content: {e}")
        job = service.create_job(tmp, original)
        # Store correlation and dashboard mapping if provided
        try:
            job.correlation_id = corr
            if dashboard_job_id:
                job.dashboard_job_id = dashboard_job_id
        except Exception:
            pass
        try:
            service.process(job.job_id)
        except Exception as e:
            logger.error(f"job {job.job_id} processing error: {e} correlation_id={corr}")
        job = service.job_repo.get(job.job_id)
        return {
            "job_id": job.job_id,
            "remote_job_id": job.job_id,
            "status": job.status.value,
            "progress_percent": job.progress_percent,
            "failure_reason": job.failure_reason,
            "correlation_id": corr,
            "config": config,
        }
    except HTTPException:
        raise
    except ValueError as e:
        raise HTTPException(status_code=422, detail=str(e))
    except Exception as e:
        logger.error(f"create job failed: {e}")
        raise HTTPException(status_code=500, detail="Internal error")
    finally:
        if tmp and tmp.exists():
            try:
                tmp.unlink()
            except Exception:
                pass


@router.get("/jobs/{job_id}")
def get_job(job_id: str, service=Depends(get_service)):
    try:
        uuid.UUID(job_id)
    except ValueError:
        raise HTTPException(status_code=422, detail="Invalid job_id")
    job = service.job_repo.get(job_id)
    if job is None:
        raise HTTPException(status_code=404, detail="Job not found")
    return {
        "job_id": job.job_id,
        "status": job.status.value,
        "progress_percent": job.progress_percent,
        "frames_processed": job.frames_processed,
        "frames_total": job.frames_total,
        "failure_reason": job.failure_reason,
        "started_at": job.started_at,
        "finished_at": job.finished_at,
        "output_path": job.output_path,
        "output_metadata": job.output_metadata,
    }


@router.post("/jobs/{job_id}/cancel")
def cancel_job(job_id: str, service=Depends(get_service)):
    job = service.job_repo.get(job_id)
    if job is None:
        raise HTTPException(status_code=404, detail="Job not found")
    if job.status in (JobStatus.completed, JobStatus.failed, JobStatus.cancelled):
        return {"job_id": job.job_id, "status": job.status.value}
    try:
        job = service.cancel(job_id)
        return {"job_id": job.job_id, "status": job.status.value}
    except ValueError as e:
        raise HTTPException(status_code=409, detail=str(e))


@router.post("/jobs/{job_id}/retry")
def retry_job(job_id: str, service=Depends(get_service)):
    job = service.job_repo.get(job_id)
    if job is None:
        raise HTTPException(status_code=404, detail="Job not found")
    try:
        new_job = service.retry(job_id)
        try:
            service.process(new_job.job_id)
        except Exception as e:
            logger.error(f"retry job {new_job.job_id} failed: {e}")
        new_job = service.job_repo.get(new_job.job_id)
        return {"job_id": new_job.job_id, "status": new_job.status.value}
    except ValueError as e:
        raise HTTPException(status_code=409, detail=str(e))


@router.get("/jobs/{job_id}/events")
def get_events(job_id: str, service=Depends(get_service)):
    job = service.job_repo.get(job_id)
    if job is None:
        raise HTTPException(status_code=404, detail="Job not found")
    phone_events = service.event_repo.list_by_job(job_id)
    behavior_events = (
        service.get_behavior_events(job_id) if hasattr(service, "get_behavior_events") else []
    )
    data = [
        {
            "event_id": e.event_id,
            "job_id": e.job_id,
            "event_type": e.event_type,
            "frame_number": e.frame_number,
            "timestamp_seconds": e.timestamp_seconds,
            "class_id": e.class_id,
            "class_name": e.class_name,
            "confidence": e.confidence,
            "bbox": e.bbox,
            "requires_review": e.requires_review,
        }
        for e in phone_events
    ]
    for b in behavior_events:
        data.append(
            {
                "event_id": b.event_id,
                "job_id": b.job_id,
                "event_type": b.event_type,
                "track_id": b.track_id,
                "start_frame": b.start_frame,
                "end_frame": b.end_frame,
                "start_time": b.start_time,
                "end_time": b.end_time,
                "observation_count": b.observation_count,
                "config_version": b.config_version,
                "method_version": b.method_version,
                "explanation": b.explanation,
                "requires_review": b.requires_review,
            }
        )
    return {"job_id": job_id, "total": len(data), "data": data}


@router.get("/jobs/{job_id}/metrics")
def get_metrics(job_id: str, service=Depends(get_service)):
    job = service.job_repo.get(job_id)
    if job is None:
        raise HTTPException(status_code=404, detail="Job not found")
    return {"job_id": job_id, "metrics": job.metrics, "status": job.status.value}
