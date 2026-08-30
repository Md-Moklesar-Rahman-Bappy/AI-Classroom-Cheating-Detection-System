from pydantic import Field
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    app_name: str = "AI Classroom Cheating Detection - AI Service"
    app_version: str = "0.2.0"
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
