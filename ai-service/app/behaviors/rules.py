import uuid

from ..orientation.models import OrientationObservation
from .config import BehaviorConfig
from .models import BehaviorEvent


class TemporalRule:
    def __init__(self, config: BehaviorConfig):
        self.config = config
        self.buffers: dict[int, list[OrientationObservation]] = {}
        self.last_event_frame: dict[int, int] = {}
        self.active_events: dict[int, bool] = {}

    def _buffer_for(self, track_id: int) -> list[OrientationObservation]:
        return self.buffers.setdefault(track_id, [])

    def _should_suppress(self, track_id: int, frame: int) -> bool:
        last = self.last_event_frame.get(track_id)
        if last is None:
            return False
        return (frame - last) < self.config.cooldown_frames

    def _prune(self, track_id: int):
        buf = self._buffer_for(track_id)
        if len(buf) > self.config.window_size:
            self.buffers[track_id] = buf[-self.config.window_size :]

    def observe(self, obs: OrientationObservation, frame: int) -> BehaviorEvent | None:
        raise NotImplementedError

    def _make_event(
        self,
        track_id: int,
        job_id: str,
        typ: str,
        buf: list[OrientationObservation],
        frame: int,
        missing: int,
    ) -> BehaviorEvent:
        start = buf[0]
        end = buf[-1]
        return BehaviorEvent(
            event_id=str(uuid.uuid4()),
            job_id=job_id,
            track_id=track_id,
            event_type=typ,
            start_frame=frame - len(buf) + 1,
            end_frame=frame,
            start_time=start.timestamp,
            end_time=end.timestamp,
            observation_count=len(buf),
            supporting_observations=len(
                [o for o in buf if o.orientation_state in ("left", "right", "backward", "forward")]
            ),
            missing_observations=missing,
            config_version=self.config.config_version,
            method_version=end.method_version,
            explanation=f"{typ} with {len(buf)} obs window, min_supporting={self.config.min_supporting}, missing={missing}",
        )


class RepeatedLookingLeftRule(TemporalRule):
    def observe(self, obs: OrientationObservation, frame: int) -> BehaviorEvent | None:
        buf = self._buffer_for(obs.track_id)
        buf.append(obs)
        self._prune(obs.track_id)
        if self._should_suppress(obs.track_id, frame):
            return None
        left_count = sum(1 for o in buf if o.orientation_state == "left")
        missing = sum(1 for o in buf if o.orientation_state in ("uncertain", "unavailable"))
        if (
            left_count >= self.config.min_supporting
            and missing <= self.config.max_missing
            and len(buf) >= self.config.min_duration_frames
        ):
            if left_count / len(buf) >= 0.5:
                self.last_event_frame[obs.track_id] = frame
                return self._make_event(
                    obs.track_id, "", "Repeated Looking Left", buf, frame, missing
                )
        return None

    def observe_with_job(self, obs, frame, job_id):
        buf = self._buffer_for(obs.track_id)
        buf.append(obs)
        self._prune(obs.track_id)
        if self._should_suppress(obs.track_id, frame):
            return None
        left_count = sum(1 for o in buf if o.orientation_state == "left")
        missing = sum(1 for o in buf if o.orientation_state in ("uncertain", "unavailable"))
        if (
            left_count >= self.config.min_supporting
            and missing <= self.config.max_missing
            and len(buf) >= self.config.min_duration_frames
        ):
            if left_count / len(buf) >= 0.5:
                self.last_event_frame[obs.track_id] = frame
                return self._make_event(
                    obs.track_id, job_id, "Repeated Looking Left", buf, frame, missing
                )
        return None


class RepeatedLookingRightRule(TemporalRule):
    def observe_with_job(self, obs, frame, job_id):
        buf = self._buffer_for(obs.track_id)
        buf.append(obs)
        self._prune(obs.track_id)
        if self._should_suppress(obs.track_id, frame):
            return None
        cnt = sum(1 for o in buf if o.orientation_state == "right")
        missing = sum(1 for o in buf if o.orientation_state in ("uncertain", "unavailable"))
        if (
            cnt >= self.config.min_supporting
            and missing <= self.config.max_missing
            and len(buf) >= self.config.min_duration_frames
        ):
            if cnt / len(buf) >= 0.5:
                self.last_event_frame[obs.track_id] = frame
                return self._make_event(
                    obs.track_id, job_id, "Repeated Looking Right", buf, frame, missing
                )
        return None


class LookingBackwardRule(TemporalRule):
    def observe_with_job(self, obs, frame, job_id):
        buf = self._buffer_for(obs.track_id)
        buf.append(obs)
        self._prune(obs.track_id)
        if self._should_suppress(obs.track_id, frame):
            return None
        cnt = sum(1 for o in buf if o.orientation_state == "backward")
        missing = sum(1 for o in buf if o.orientation_state in ("uncertain", "unavailable"))
        if (
            cnt >= max(3, self.config.min_supporting // 2)
            and missing <= self.config.max_missing
            and len(buf) >= self.config.min_duration_frames
        ):
            if cnt / len(buf) >= 0.3:
                self.last_event_frame[obs.track_id] = frame
                return self._make_event(
                    obs.track_id, job_id, "Looking Backward", buf, frame, missing
                )
        return None


class LeavingSeatRule(TemporalRule):
    def __init__(self, config: BehaviorConfig):
        super().__init__(config)
        self.absence: dict[int, int] = {}
        self.last_seen: dict[int, int] = {}

    def mark_seen(self, track_id: int, frame: int):
        self.last_seen[track_id] = frame
        self.absence[track_id] = 0

    def mark_missing(self, track_id: int, frame: int) -> BehaviorEvent | None:
        if track_id not in self.last_seen:
            return None
        if self._should_suppress(track_id, frame):
            return None
        absence = frame - self.last_seen[track_id]
        self.absence[track_id] = absence
        if absence >= self.config.leaving_absence_frames:
            if self.last_event_frame.get(track_id, -999) + self.config.cooldown_frames > frame:
                return None
            self.last_event_frame[track_id] = frame
            return BehaviorEvent(
                event_id=str(uuid.uuid4()),
                job_id="",
                track_id=track_id,
                event_type="Leaving Seat",
                start_frame=self.last_seen[track_id],
                end_frame=frame,
                start_time=0,
                end_time=0,
                observation_count=absence,
                supporting_observations=absence,
                missing_observations=absence,
                config_version=self.config.config_version,
                method_version="centroid-v1",
                explanation=f"Prolonged absence {absence} frames >= {self.config.leaving_absence_frames} (MVP proxy: track missing)",
            )
        return None

    def observe_with_job(self, obs, frame, job_id):
        return None


class InsufficientEvidenceRule:
    def check(self, obs: OrientationObservation) -> bool:
        return obs.orientation_state in ("uncertain", "unavailable") or obs.measurement_quality in (
            "low",
            "unavailable",
        )
