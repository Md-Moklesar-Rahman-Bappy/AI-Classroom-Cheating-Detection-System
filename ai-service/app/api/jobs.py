import tempfile
import uuid
from pathlib import Path

from fastapi import APIRouter, Depends, File, HTTPException, UploadFile

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
    file: UploadFile = File(...),
    service=Depends(get_service),
):
    original = file.filename or "upload.mp4"
    if ".." in original or "/" in original or "\\" in original:
        raise HTTPException(status_code=422, detail="Invalid filename")
    suffix = Path(original).suffix.lower()
    if suffix and suffix not in {".mp4", ".avi", ".mov", ".mkv"}:
        raise HTTPException(status_code=422, detail="Unsupported file type")
    tmp = None
    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=suffix or ".mp4") as t:
            tmp = Path(t.name)
            content = await file.read()
            max_bytes = service.max_upload_mb * 1024 * 1024
            if len(content) > max_bytes:
                raise HTTPException(status_code=422, detail="File too large")
            if len(content) == 0:
                raise HTTPException(status_code=422, detail="Empty file")
            t.write(content)
        job = service.create_job(tmp, original)
        try:
            service.process(job.job_id)
        except Exception as e:
            logger.error(f"job {job.job_id} processing error: {e}")
        job = service.job_repo.get(job.job_id)
        return {
            "job_id": job.job_id,
            "status": job.status.value,
            "progress_percent": job.progress_percent,
            "failure_reason": job.failure_reason,
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
