from ..orientation.models import OrientationObservation
from .config import BehaviorConfig
from .models import BehaviorEvent
from .rules import (
    InsufficientEvidenceRule,
    LeavingSeatRule,
    LookingBackwardRule,
    RepeatedLookingLeftRule,
    RepeatedLookingRightRule,
)


class TemporalEventEngine:
    def __init__(self, config: BehaviorConfig):
        self.config = config
        self.left_rule = RepeatedLookingLeftRule(config)
        self.right_rule = RepeatedLookingRightRule(config)
        self.backward_rule = LookingBackwardRule(config)
        self.leaving_rule = LeavingSeatRule(config)
        self.insufficient_rule = InsufficientEvidenceRule()
        self.events: list[BehaviorEvent] = []

    def process_observation(
        self, obs: OrientationObservation, frame: int, job_id: str
    ) -> list[BehaviorEvent]:
        emitted: list[BehaviorEvent] = []
        for rule, typ in [
            (self.left_rule, "left"),
            (self.right_rule, "right"),
            (self.backward_rule, "backward"),
        ]:
            ev = rule.observe_with_job(obs, frame, job_id)
            if ev:
                ev.job_id = job_id
                self.events.append(ev)
                emitted.append(ev)

        if obs.orientation_state in ("uncertain", "unavailable"):
            pass

        return emitted

    def mark_seen(self, track_id: int, frame: int):
        self.leaving_rule.mark_seen(track_id, frame)

    def mark_missing_tracks(
        self, missing_ids: list[int], frame: int, job_id: str
    ) -> list[BehaviorEvent]:
        emitted = []
        for tid in missing_ids:
            ev = self.leaving_rule.mark_missing(tid, frame)
            if ev:
                ev.job_id = job_id
                self.events.append(ev)
                emitted.append(ev)
        return emitted

    def is_insufficient(self, obs: OrientationObservation) -> bool:
        return self.insufficient_rule.check(obs)
