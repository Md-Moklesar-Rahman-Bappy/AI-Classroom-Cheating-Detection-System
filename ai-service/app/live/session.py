import queue
import threading
import time
import uuid
from dataclasses import dataclass, field

import cv2
import numpy as np

from ..behaviors.config import BehaviorConfig
from ..behaviors.engine import TemporalEventEngine
from ..core.logging import get_logger
from ..detection.base import ObjectDetector
from ..inputs.camera_config import CameraSourceConfig, HealthState, SourceState
from ..orientation.geometric import GeometricOrientationEstimator
from ..rendering.renderer import BoundingBoxRenderer
from ..tracking.centroid_tracker import SimpleCentroidTracker

logger = get_logger(__name__)


@dataclass
class LiveMetrics:
    fps: float = 0.0
    latency_ms: float = 0.0
    last_frame_time: float = 0.0
    frame_count: int = 0
    dropped_frames: int = 0
    reconnect_count: int = 0
    alert_latency_ms: float = 0.0


@dataclass
class LiveSession:
    session_id: str
    config: CameraSourceConfig
    state: SourceState = SourceState.unconfigured
    health: HealthState = HealthState.unknown
    last_frame_timestamp: float = 0.0
    metrics: LiveMetrics = field(default_factory=LiveMetrics)
    events: list = field(default_factory=list)
    preview_queue: queue.Queue = field(default_factory=lambda: queue.Queue(maxsize=2))
    stop_token: threading.Event = field(default_factory=threading.Event)
    thread: threading.Thread | None = None
    lock: threading.Lock = field(default_factory=threading.Lock)
    start_time: float | None = None
    error: str | None = None


_sessions: dict[str, LiveSession] = {}
_sessions_lock = threading.Lock()
_single_source_limit = threading.Semaphore(1)


def _get_input_source(config: CameraSourceConfig):
    if config.source_type == "webcam":
        from ..inputs.webcam import WebcamInput

        try:
            idx = int(config.identifier)
        except ValueError:
            idx = 0
        return WebcamInput(device_index=idx)
    elif config.source_type in ("rtsp", "http"):
        from ..inputs.rtsp import RtspStreamInput

        return RtspStreamInput(url=config.identifier, timeout_ms=config.timeout_ms)
    else:
        from ..inputs.test_input import TestVideoInput

        return TestVideoInput(config.identifier) if config.identifier else _synthetic_input()


def _synthetic_input():
    class SyntheticInput:
        def __init__(self):
            self.cap = None
            self._open = False

        def open(self):
            self._open = True

        def metadata(self):
            from ..inputs.base import VideoMetadata

            return VideoMetadata(
                width=640, height=360, fps=15, frame_count=-1, codec="raw", duration_seconds=0
            )

        def frames(self):
            idx = 0
            while self._open:
                frame = np.zeros((360, 640, 3), dtype=np.uint8)
                cv2.circle(frame, (320 + int(20 * np.sin(idx * 0.1)), 180), 20, (255, 255, 255), -1)
                from ..inputs.base import FramePacket

                yield FramePacket(frame=frame, frame_index=idx, timestamp_seconds=idx / 15.0)
                idx += 1
                time.sleep(0.066)

        def close(self):
            self._open = False

    return SyntheticInput()


def _bounded_delay(attempt: int, base_ms: int, max_ms: int) -> float:
    delay = base_ms * (2 ** (attempt - 1))
    return min(delay, max_ms) / 1000.0


def _run_live(session: LiveSession, detector: ObjectDetector):
    tracker = SimpleCentroidTracker(max_distance=80, max_missing=10)
    orientation = GeometricOrientationEstimator()
    behavior_config = BehaviorConfig()
    engine = TemporalEventEngine(behavior_config)
    renderer = BoundingBoxRenderer()
    reconnect_attempt = 0
    stale_count = 0
    last_frame_time = time.time()

    with _single_source_limit:
        session.state = SourceState.connected
        session.health = HealthState.healthy
        session.start_time = time.time()

        while not session.stop_token.is_set():
            cap = None
            try:
                source = _get_input_source(session.config)
                source.open()
                session.state = SourceState.monitoring
                session.health = HealthState.healthy
                reconnect_attempt = 0
                stale_count = 0

                for packet in source.frames():
                    if session.stop_token.is_set():
                        break
                    now = time.time()
                    if now - last_frame_time > session.config.frame_timeout_ms / 1000.0:
                        stale_count += 1
                        session.health = HealthState.degraded
                        if stale_count >= session.config.max_stale_frames:
                            session.state = SourceState.reconnecting
                            break
                    else:
                        stale_count = 0
                        session.health = HealthState.healthy
                    last_frame_time = now
                    session.last_frame_timestamp = now
                    session.metrics.last_frame_time = now
                    session.metrics.frame_count += 1

                    t0 = time.time()
                    try:
                        dets = detector.detect(packet.frame)
                    except Exception as e:
                        logger.error(f"live detect failed: {e}")
                        continue
                    latency = (time.time() - t0) * 1000
                    session.metrics.latency_ms = latency

                    tracks = tracker.update(dets)
                    observations = [
                        orientation.estimate(tr, packet.timestamp_seconds) for tr in tracks
                    ]
                    for obs in observations:
                        engine.mark_seen(obs.track_id, packet.frame_index)
                        evs = engine.process_observation(
                            obs, packet.frame_index, session.session_id
                        )
                        for ev in evs:
                            session.events.append(ev)
                            session.metrics.alert_latency_ms = (time.time() - t0) * 1000

                    missing = []
                    for tid in list(engine.leaving_rule.last_seen.keys()):
                        if tid not in {tr.track_id for tr in tracks}:
                            missing.append(tid)
                    if missing:
                        evs = engine.mark_missing_tracks(
                            missing, packet.frame_index, session.session_id
                        )
                        session.events.extend(evs)

                    active_events = [e for e in session.events if e.end_frame == packet.frame_index]
                    annotated = renderer.render(
                        packet.frame, dets, tracks, observations, active_events
                    )

                    small = cv2.resize(annotated, (320, 180))
                    _, jpeg = cv2.imencode(".jpg", small)
                    if jpeg is not None:
                        try:
                            session.preview_queue.put_nowait(jpeg.tobytes())
                        except queue.Full:
                            try:
                                session.preview_queue.get_nowait()
                            except queue.Empty:
                                pass
                            try:
                                session.preview_queue.put_nowait(jpeg.tobytes())
                            except queue.Full:
                                pass

                    elapsed = time.time() - session.start_time if session.start_time else 1
                    session.metrics.fps = session.metrics.frame_count / max(1, elapsed)

                    if session.metrics.frame_count % 10 == 0:
                        logger.info(f"live {session.session_id} fps={session.metrics.fps:.1f}")

            except Exception as e:
                session.error = str(e)[:500]
                session.health = HealthState.unhealthy
                session.state = SourceState.reconnecting
                logger.error(f"live source error {session.session_id}: {e}")
            finally:
                try:
                    if "source" in locals() and source:
                        source.close()
                except Exception:
                    pass
                if session.stop_token.is_set():
                    break
                if session.state == SourceState.reconnecting and not session.stop_token.is_set():
                    reconnect_attempt += 1
                    if reconnect_attempt > session.config.reconnect_max_attempts:
                        session.state = SourceState.failed
                        session.health = HealthState.unhealthy
                        break
                    delay = _bounded_delay(
                        reconnect_attempt,
                        session.config.reconnect_base_delay_ms,
                        session.config.reconnect_max_delay_ms,
                    )
                    session.metrics.reconnect_count += 1
                    logger.info(
                        f"reconnecting {session.session_id} attempt {reconnect_attempt} delay {delay}s"
                    )
                    time.sleep(delay)
                elif not session.stop_token.is_set():
                    session.state = SourceState.disconnected
                    break

        session.state = SourceState.stopped if session.stop_token.is_set() else session.state
        if session.state not in (SourceState.failed, SourceState.stopped):
            session.state = SourceState.stopped


def create_session(config: CameraSourceConfig) -> LiveSession:
    session_id = str(uuid.uuid4())
    session = LiveSession(session_id=session_id, config=config, state=SourceState.testing)
    with _sessions_lock:
        _sessions[session_id] = session
    return session


def get_session(session_id: str) -> LiveSession | None:
    with _sessions_lock:
        return _sessions.get(session_id)


def list_sessions() -> list[LiveSession]:
    with _sessions_lock:
        return list(_sessions.values())


def start_session(session_id: str, detector: ObjectDetector) -> LiveSession:
    session = get_session(session_id)
    if not session:
        raise KeyError(f"session {session_id} not found")
    with session.lock:
        if session.state in (
            SourceState.monitoring,
            SourceState.connected,
            SourceState.reconnecting,
        ):
            raise ValueError("already monitoring")
        if session.thread and session.thread.is_alive():
            raise ValueError("already running")
        session.stop_token.clear()
        session.state = SourceState.monitoring
        session.thread = threading.Thread(target=_run_live, args=(session, detector), daemon=True)
        session.thread.start()
    return session


def stop_session(session_id: str) -> LiveSession:
    session = get_session(session_id)
    if not session:
        raise KeyError(f"session {session_id} not found")
    session.stop_token.set()
    if session.thread and session.thread.is_alive():
        session.thread.join(timeout=5)
    session.state = SourceState.stopped
    session.health = HealthState.unknown
    try:
        while not session.preview_queue.empty():
            session.preview_queue.get_nowait()
    except queue.Empty:
        pass
    return session


def delete_session(session_id: str) -> None:
    with _sessions_lock:
        sess = _sessions.get(session_id)
        if sess:
            sess.stop_token.set()
            if sess.thread and sess.thread.is_alive():
                sess.thread.join(timeout=2)
            _sessions.pop(session_id, None)
