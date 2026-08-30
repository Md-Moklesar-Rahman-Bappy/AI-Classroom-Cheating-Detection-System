import time

from fastapi import APIRouter

from ..config.settings import settings
from ..schemas.models import HealthResponse, VersionResponse

router = APIRouter()
_start = time.time()
_detector_ref = None


def set_detector(det):
    global _detector_ref
    _detector_ref = det


@router.get("/health", response_model=HealthResponse)
def health():
    loaded = _detector_ref is not None and _detector_ref.is_loaded()
    return HealthResponse(
        model_loaded=loaded,
        version=settings.app_version,
        uptime_seconds=time.time() - _start,
    )


@router.get("/version", response_model=VersionResponse)
def version():
    loaded = _detector_ref is not None and _detector_ref.is_loaded()
    checksum = getattr(_detector_ref, "checksum", None) if _detector_ref else None
    ver = None
    try:
        import ultralytics

        ver = ultralytics.__version__
    except Exception:
        pass
    return VersionResponse(
        version=settings.app_version,
        model_path=settings.model_path,
        model_loaded=loaded,
        model_checksum=checksum,
        allowed_classes=settings.allowed_classes,
        ul_version=ver,
    )
