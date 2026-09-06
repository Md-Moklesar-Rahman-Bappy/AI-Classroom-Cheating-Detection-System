from ..orientation.models import OrientationObservation
from .config import BehaviorConfig
from .models import BehaviorEvent
from .rules import (
    ExcessiveHeadMovementRule,
    InsufficientEvidenceRule,
    LeavingSeatRule,
    LookingBackwardRule,
    RepeatedLookingLeftRule,
    RepeatedLookingRightRule,
    TrackingLostRule,
)


class TemporalEventEngine:
    def __init__(self, config: BehaviorConfig):
        self.config = config
        self.left_rule = RepeatedLookingLeftRule(config)
        self.right_rule = RepeatedLookingRightRule(config)
        self.backward_rule = LookingBackwardRule(config)
        self.head_movement_rule = ExcessiveHeadMovementRule(config)
        self.leaving_rule = LeavingSeatRule(config)
        self.tracking_lost_rule = TrackingLostRule(config)
        self.insufficient_rule = InsufficientEvidenceRule()
        self.events: list[BehaviorEvent] = []

    def process_observation(
        self, obs: OrientationObservation, frame: int, job_id: str
    ) -> list[BehaviorEvent]:
        emitted: list[BehaviorEvent] = []
        for rule in [
            self.left_rule,
            self.right_rule,
            self.backward_rule,
            self.head_movement_rule,
        ]:
            ev = rule.observe_with_job(obs, frame, job_id)
            if ev:
                ev.job_id = job_id
                self.events.append(ev)
                emitted.append(ev)

        if obs.orientation_state in ("uncertain", "unavailable"):
            pass

        return emitted

    def mark_seen(self, track_id: int, frame: int, bbox: dict | None = None, timestamp: float = 0):
        self.leaving_rule.mark_seen(track_id, frame, bbox=bbox, timestamp=timestamp)
        self.tracking_lost_rule.mark_seen(track_id, frame, bbox=bbox, timestamp=timestamp)

    def mark_missing_tracks(
        self, missing_ids: list[int], frame: int, job_id: str, timestamp: float = 0
    ) -> list[BehaviorEvent]:
        emitted = []
        for tid in missing_ids:
            ev_lost = self.tracking_lost_rule.mark_missing(tid, frame, timestamp=timestamp)
            if ev_lost:
                ev_lost.job_id = job_id
                self.events.append(ev_lost)
                emitted.append(ev_lost)
                continue
            ev = self.leaving_rule.mark_missing(tid, frame, timestamp=timestamp)
            if ev:
                ev.job_id = job_id
                self.events.append(ev)
                emitted.append(ev)
        return emitted

    def is_insufficient(self, obs: OrientationObservation) -> bool:
        return self.insufficient_rule.check(obs)
