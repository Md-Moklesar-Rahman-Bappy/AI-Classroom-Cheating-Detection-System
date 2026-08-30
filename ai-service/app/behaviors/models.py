from dataclasses import dataclass


@dataclass
class BehaviorEvent:
    event_id: str
    job_id: str
    track_id: int
    event_type: str
    start_frame: int
    end_frame: int
    start_time: float
    end_time: float
    observation_count: int
    supporting_observations: int
    missing_observations: int
    config_version: str
    method_version: str
    explanation: str
    requires_review: bool = True
