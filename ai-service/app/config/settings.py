from pydantic import Field
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    app_name: str = "AI Classroom Cheating Detection - AI Service"
    app_version: str = "0.4.0"
    environment: str = Field(default="development", description="development|production")
    debug: bool = False
    log_level: str = "INFO"

    model_path: str = "yolo11n.pt"
    model_conf_threshold: float = 0.25
    model_iou_threshold: float = 0.45
    model_image_size: int = 640
    allowed_classes: list[int] = [0, 67]

    input_width: int = 640
    input_height: int = 360
    process_every_n_frames: int = 3

    evidence_dir: str = "evidence"
    output_dir: str = "outputs"
    storage_dir: str = "storage"
    max_upload_mb: int = 500
    event_cooldown_frames: int = 30
    enable_evidence: bool = True

    phone_conf_threshold: float = 0.40
    phone_min_width: int = 30
    phone_min_height: int = 30
    phone_min_area: int = 1200
    phone_aspect_min: float = 0.35
    phone_aspect_max: float = 2.20

    tracking_max_distance: float = 90.0
    tracking_max_missing: int = 15

    orientation_left_threshold: float = -0.15
    orientation_right_threshold: float = 0.15
    orientation_backward_aspect: float = 1.8
    orientation_method_version: str = "geometric-v1"

    behavior_window_size: int = 15
    behavior_min_supporting: int = 8
    behavior_max_missing: int = 4
    behavior_min_duration: int = 10
    behavior_cooldown_frames: int = 45
    behavior_leaving_absence: int = 45
    behavior_config_version: str = "v2.1-accuracy"

    multiple_persons_threshold: int = 2
    multiple_persons_iou_threshold: float = 0.3
    head_movement_switch_threshold: int = 4
    head_movement_window: int = 15
    tracking_lost_frames: int = 15
    tracking_lost_cooldown: int = 30

    allowed_video_mimes: list[str] = [
        "video/mp4",
        "video/avi",
        "video/quicktime",
        "video/x-msvideo",
        "video/x-matroska",
    ]
    allowed_video_exts: list[str] = [".mp4", ".avi", ".mov", ".mkv"]

    ai_service_token: str = Field(
        default="dev-token-change-me",
        description="Service token, must be overridden in .env",
    )

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"
        extra = "ignore"

    def is_development(self) -> bool:
        return self.environment == "development"


settings = Settings()
