import time
import uuid
from dataclasses import dataclass, field
from enum import Enum


class JobStatus(str, Enum):
    pending = "pending"
    queued = "queued"
    processing = "processing"
    cancelling = "cancelling"
    cancelled = "cancelled"
    failed = "failed"
    completed = "completed"


VALID_TRANSITIONS: dict[JobStatus, set[JobStatus]] = {
    JobStatus.pending: {
        JobStatus.queued,
        JobStatus.processing,
        JobStatus.cancelled,
        JobStatus.failed,
    },
    JobStatus.queued: {
        JobStatus.processing,
        JobStatus.cancelling,
        JobStatus.cancelled,
        JobStatus.failed,
    },
    JobStatus.processing: {
        JobStatus.cancelling,
        JobStatus.cancelled,
        JobStatus.failed,
        JobStatus.completed,
    },
    JobStatus.cancelling: {JobStatus.cancelled, JobStatus.failed},
    JobStatus.cancelled: set(),
    JobStatus.failed: set(),
    JobStatus.completed: set(),
}


def can_transition(from_status: JobStatus, to_status: JobStatus) -> bool:
    return to_status in VALID_TRANSITIONS.get(from_status, set())


@dataclass
class AnalysisJob:
    job_id: str = field(default_factory=lambda: str(uuid.uuid4()))
    status: JobStatus = JobStatus.pending
    input_path: str = ""
    original_filename: str = ""
    output_path: str | None = None
    progress_percent: float = 0.0
    frames_total: int = -1
    frames_processed: int = 0
    frames_skipped: int = 0
    detection_invocations: int = 0
    source_fps: float | None = None
    error_count: int = 0
    event_count: int = 0
    failure_reason: str | None = None
    created_at: float = field(default_factory=time.time)
    started_at: float | None = None
    finished_at: float | None = None
    metrics: dict = field(default_factory=dict)
    output_metadata: dict | None = None
    cancel_requested: bool = False

    def transition(self, target: JobStatus) -> None:
        if self.status == target:
            return
        if not can_transition(self.status, target):
            raise ValueError(f"Invalid transition {self.status} -> {target}")
        self.status = target
        if target == JobStatus.processing and self.started_at is None:
            self.started_at = time.time()
        if target in (JobStatus.completed, JobStatus.failed, JobStatus.cancelled):
            self.finished_at = time.time()

    def request_cancel(self) -> None:
        self.cancel_requested = True
        if self.status in (JobStatus.pending, JobStatus.queued):
            self.transition(JobStatus.cancelled)
        elif self.status == JobStatus.processing:
            self.transition(JobStatus.cancelling)
