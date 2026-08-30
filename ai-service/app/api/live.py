import time
import uuid

from fastapi import APIRouter, Depends, HTTPException, Request
from fastapi.responses import StreamingResponse

from ..config.settings import settings
from ..inputs.camera_config import CameraSourceConfig, SourceState
from ..live.session import (
    create_session,
    get_session,
    list_sessions,
    start_session,
    stop_session,
)

router = APIRouter()
_detector = None
_audit_log = []


def set_detector(detector):
    global _detector
    _detector = detector


def require_auth(request: Request):
    token = request.headers.get("Authorization")
    expected = settings.ai_service_token
    if expected and expected != "dev-token-change-me":
        if token != f"Bearer {expected}":
            raise HTTPException(status_code=401, detail="Unauthorized")
    return True


def _audit(action: str, session_id: str, request: Request):
    _audit_log.append(
        {
            "action": action,
            "session_id": session_id,
            "ip": request.client.host if request.client else "unknown",
            "time": time.time(),
            "correlation_id": request.headers.get("X-Correlation-Id", str(uuid.uuid4())),
        }
    )


@router.post("/live/start")
def live_start(request: Request, payload: dict, auth=Depends(require_auth)):
    if _detector is None or not _detector.is_loaded():
        raise HTTPException(status_code=503, detail="Model not loaded")
    source_type = payload.get("source_type", "webcam")
    identifier = payload.get("identifier", "0")
    if not identifier:
        raise HTTPException(status_code=422, detail="identifier required")
    if source_type not in ("webcam", "rtsp", "http", "test", "test_source", "test_stream"):
        raise HTTPException(status_code=422, detail="unsupported source_type")
    if len(identifier) > 500:
        raise HTTPException(status_code=422, detail="identifier too long")
    config = CameraSourceConfig(source_type=source_type, identifier=str(identifier))
    try:
        config.validate()
    except ValueError as e:
        raise HTTPException(status_code=422, detail=str(e))
    sessions = list_sessions()
    active = [
        s
        for s in sessions
        if s.state in (SourceState.monitoring, SourceState.connected, SourceState.reconnecting)
    ]
    if len(active) >= 1:
        raise HTTPException(status_code=409, detail="single-source limit: already monitoring")
    session = create_session(config)
    try:
        start_session(session.session_id, _detector)
    except ValueError as e:
        raise HTTPException(status_code=409, detail=str(e))
    _audit("live_start", session.session_id, request)
    return {
        "session_id": session.session_id,
        "status": session.state.value,
        "source_type": source_type,
    }


@router.post("/live/{session_id}/stop")
def live_stop(session_id: str, request: Request, auth=Depends(require_auth)):
    try:
        uuid.UUID(session_id)
    except ValueError:
        raise HTTPException(status_code=422, detail="invalid session_id")
    sess = get_session(session_id)
    if not sess:
        raise HTTPException(status_code=404, detail="session not found")
    if sess.state == SourceState.stopped:
        _audit("live_stop_idempotent", session_id, request)
        return {"session_id": session_id, "status": sess.state.value}
    stop_session(session_id)
    _audit("live_stop", session_id, request)
    return {"session_id": session_id, "status": SourceState.stopped.value}


@router.get("/live/{session_id}/health")
def live_health(session_id: str, request: Request, auth=Depends(require_auth)):
    try:
        uuid.UUID(session_id)
    except ValueError:
        raise HTTPException(status_code=422, detail="invalid session_id")
    sess = get_session(session_id)
    if not sess:
        raise HTTPException(status_code=404, detail="session not found")
    return {
        "session_id": session_id,
        "state": sess.state.value,
        "health": sess.health.value,
        "last_frame_time": sess.last_frame_timestamp,
        "metrics": {
            "fps": sess.metrics.fps,
            "latency_ms": sess.metrics.latency_ms,
            "frame_count": sess.metrics.frame_count,
            "reconnect_count": sess.metrics.reconnect_count,
        },
        "error": sess.error,
    }


@router.get("/live/{session_id}/events")
def live_events(session_id: str, request: Request, auth=Depends(require_auth)):
    try:
        uuid.UUID(session_id)
    except ValueError:
        raise HTTPException(status_code=422, detail="invalid session_id")
    sess = get_session(session_id)
    if not sess:
        raise HTTPException(status_code=404, detail="session not found")
    return {
        "session_id": session_id,
        "total": len(sess.events),
        "events": [
            {
                "event_id": getattr(e, "event_id", str(uuid.uuid4())),
                "track_id": getattr(e, "track_id", 0),
                "event_type": getattr(e, "event_type", "unknown"),
                "start_frame": getattr(e, "start_frame", 0),
                "end_frame": getattr(e, "end_frame", 0),
                "explanation": getattr(e, "explanation", ""),
            }
            for e in sess.events[-20:]
        ],
    }


@router.get("/live/{session_id}/preview")
def live_preview(session_id: str, request: Request, auth=Depends(require_auth)):
    try:
        uuid.UUID(session_id)
    except ValueError:
        raise HTTPException(status_code=422, detail="invalid session_id")
    sess = get_session(session_id)
    if not sess:
        raise HTTPException(status_code=404, detail="session not found")
    if sess.state not in (
        SourceState.monitoring,
        SourceState.connected,
        SourceState.degraded,
        SourceState.reconnecting,
    ):
        raise HTTPException(status_code=409, detail="not monitoring")

    def mjpeg_gen():
        while True:
            try:
                jpeg = sess.preview_queue.get(timeout=5)
                yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + jpeg + b"\r\n"
            except Exception:
                # Send placeholder if no frame
                yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + b"\xff\xd8\xff\xd9" + b"\r\n"
                time.sleep(0.5)
            if sess.state == SourceState.stopped:
                break

    return StreamingResponse(mjpeg_gen(), media_type="multipart/x-mixed-replace; boundary=frame")
