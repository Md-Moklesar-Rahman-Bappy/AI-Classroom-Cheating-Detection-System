from typing import Optional

from pydantic import BaseModel, Field


class BoundingBox(BaseModel):
    x_min: float
    y_min: float
    x_max: float
    y_max: float


class DetectionResult(BaseModel):
    class_id: int
    class_name: str
    confidence: float = Field(ge=0, le=1)
    bbox: BoundingBox


class FramePacket(BaseModel):
    frame_index: int
    timestamp_seconds: float
    width: int
    height: int

    class Config:
        arbitrary_types_allowed = True


class HealthResponse(BaseModel):
    status: str = "ok"
    model_loaded: bool
    version: str
    uptime_seconds: float


class VersionResponse(BaseModel):
    version: str
    model_path: str
    model_loaded: bool
    model_checksum: Optional[str] = None
    allowed_classes: list[int]
    ul_version: Optional[str] = None
