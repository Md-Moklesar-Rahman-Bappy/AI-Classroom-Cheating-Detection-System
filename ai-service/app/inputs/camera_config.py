from dataclasses import dataclass
from enum import Enum


class SourceState(str, Enum):
    unconfigured = "unconfigured"
    testing = "testing"
    connected = "connected"
    monitoring = "monitoring"
    reconnecting = "reconnecting"
    degraded = "degraded"
    disconnected = "disconnected"
    stopped = "stopped"
    failed = "failed"


class HealthState(str, Enum):
    healthy = "healthy"
    degraded = "degraded"
    unhealthy = "unhealthy"
    unknown = "unknown"


@dataclass
class CameraSourceConfig:
    source_type: str
    identifier: str
    width: int = 640
    height: int = 360
    fps: int = 15
    timeout_ms: int = 5000
    reconnect_max_attempts: int = 5
    reconnect_base_delay_ms: int = 1000
    reconnect_max_delay_ms: int = 30000
    frame_timeout_ms: int = 3000
    max_stale_frames: int = 5

    def validate(self) -> None:
        if not self.identifier:
            raise ValueError("identifier must be non-empty")
        if self.source_type not in ("webcam", "rtsp", "http", "test", "test_stream", "test_source"):
            raise ValueError(f"unsupported source_type {self.source_type}")
        if self.source_type in ("rtsp", "http") and "://" not in self.identifier:
            raise ValueError("Invalid stream URL: must contain ://")
