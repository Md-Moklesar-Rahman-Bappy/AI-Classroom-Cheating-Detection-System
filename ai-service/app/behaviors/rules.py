import uuid

from ..orientation.models import OrientationObservation
from .config import BehaviorConfig
from .models import (
    BEHAVIOR_CATEGORY_MAP,
    BEHAVIOR_CODE_MAP,
    BEHAVIOR_LABEL_MAP,
    BehaviorEvent,
    TwoFrameEvidence,
)


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
        code = BEHAVIOR_CODE_MAP.get(typ, "B1")
        label = BEHAVIOR_LABEL_MAP.get(typ, typ)
        category = BEHAVIOR_CATEGORY_MAP.get(code, "behavior")
        bbox = (
            end.supporting_geometry.get("bbox")
            if isinstance(end.supporting_geometry, dict)
            else None
        )
        return BehaviorEvent(
            event_id=str(uuid.uuid4()),
            job_id=job_id,
            track_id=track_id,
            event_type=typ,
            event_code=code,
            event_label=label,
            event_category=category,
            start_frame=frame - len(buf) + 1,
            end_frame=frame,
            start_time=start.timestamp,
            end_time=end.timestamp,
            frame_number=frame,
            timestamp_seconds=end.timestamp,
            bbox=bbox,
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


class ExcessiveHeadMovementRule(TemporalRule):
    def observe_with_job(self, obs, frame, job_id):
        buf = self._buffer_for(obs.track_id)
        buf.append(obs)
        window = self.config.head_movement_window or self.config.window_size
        if len(buf) > window:
            self.buffers[obs.track_id] = buf[-window:]
            buf = self.buffers[obs.track_id]
        if self._should_suppress(obs.track_id, frame):
            return None
        if len(buf) < max(6, window // 2):
            return None
        switches = 0
        valid_states = {"left", "right", "backward", "forward"}
        filtered = [o.orientation_state for o in buf if o.orientation_state in valid_states]
        if len(filtered) < 4:
            return None
        for i in range(1, len(filtered)):
            if filtered[i] != filtered[i - 1]:
                if {filtered[i], filtered[i - 1]} <= {"left", "right"} or "backward" in {
                    filtered[i],
                    filtered[i - 1],
                }:
                    switches += 1
        covers_lr = "left" in filtered and "right" in filtered
        instability = switches >= self.config.head_movement_switch_threshold and covers_lr
        if instability:
            missing = sum(1 for o in buf if o.orientation_state in ("uncertain", "unavailable"))
            if missing <= self.config.max_missing:
                self.last_event_frame[obs.track_id] = frame
                return self._make_event(
                    obs.track_id, job_id, "Excessive Head Movement", buf, frame, missing
                )
        return None


class LeavingSeatRule(TemporalRule):
    def __init__(self, config: BehaviorConfig):
        super().__init__(config)
        self.absence: dict[int, int] = {}
        self.last_seen: dict[int, int] = {}
        self.last_known_bbox: dict[int, dict] = {}
        self.last_seen_time: dict[int, float] = {}
        self.last_known_frame: dict[int, int] = {}

    def mark_seen(self, track_id: int, frame: int, bbox: dict | None = None, timestamp: float = 0):
        self.last_seen[track_id] = frame
        self.last_known_frame[track_id] = frame
        self.absence[track_id] = 0
        if bbox is not None:
            self.last_known_bbox[track_id] = dict(bbox)
        if timestamp:
            self.last_seen_time[track_id] = timestamp

    def mark_missing(self, track_id: int, frame: int, timestamp: float = 0) -> BehaviorEvent | None:
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
            last_detection_frame = self.last_known_frame.get(track_id, self.last_seen[track_id])
            last_detection_timestamp = self.last_seen_time.get(track_id, 0)
            last_detection_bbox = self.last_known_bbox.get(track_id)
            two_frame_evidence = TwoFrameEvidence(
                trigger_frame_number=frame,
                trigger_timestamp=timestamp,
                last_detection_frame_number=last_detection_frame,
                last_detection_timestamp=last_detection_timestamp,
                last_detection_bbox=last_detection_bbox,
                absence_processed_frames=absence,
                absence_source_frames=list(range(last_detection_frame + 1, frame)),
                bbox_format="xyxy",
                processed_frame_size={"width": 640, "height": 360},
                source_frame_size={"width": 64, "height": 48},
            )
            return BehaviorEvent(
                event_id=str(uuid.uuid4()),
                job_id="",
                track_id=track_id,
                event_type="Leaving Seat",
                event_code="B4",
                event_label="Possible Seat Departure",
                event_category="behavior",
                start_frame=self.last_seen[track_id],
                end_frame=frame,
                start_time=self.last_seen_time.get(track_id, 0),
                end_time=timestamp,
                frame_number=frame,
                timestamp_seconds=timestamp,
                bbox=last_detection_bbox,
                observation_count=absence,
                supporting_observations=absence,
                missing_observations=absence,
                config_version=self.config.config_version,
                method_version="centroid-v1",
                explanation=f"Prolonged absence {absence} frames >= {self.config.leaving_absence_frames} (MVP proxy: track missing)",
                two_frame_evidence=two_frame_evidence,
                last_detection_frame_number=last_detection_frame,
                last_detection_timestamp=last_detection_timestamp,
                last_detection_bbox=last_detection_bbox,
                trigger_frame_number=frame,
                trigger_timestamp=timestamp,
                absence_processed_frames=absence,
            )
        return None

    def observe_with_job(self, obs, frame, job_id):
        return None


class TrackingLostRule:
    def __init__(self, config: BehaviorConfig):
        self.config = config
        self.last_event_frame: dict[int, int] = {}
        self.last_seen: dict[int, int] = {}
        self.last_known_bbox: dict[int, dict] = {}
        self.last_seen_time: dict[int, float] = {}
        self.last_known_frame: dict[int, int] = {}

    def mark_seen(self, track_id: int, frame: int, bbox: dict | None = None, timestamp: float = 0):
        self.last_seen[track_id] = frame
        self.last_known_frame[track_id] = frame
        if bbox is not None:
            self.last_known_bbox[track_id] = dict(bbox)
        if timestamp:
            self.last_seen_time[track_id] = timestamp

    def mark_missing(self, track_id: int, frame: int, timestamp: float = 0) -> BehaviorEvent | None:
        if track_id not in self.last_seen:
            return None
        last = self.last_event_frame.get(track_id)
        if last is not None and (frame - last) < self.config.tracking_lost_cooldown:
            return None
        absence = frame - self.last_seen[track_id]
        if (
            absence >= self.config.tracking_lost_frames
            and absence < self.config.leaving_absence_frames
        ):
            self.last_event_frame[track_id] = frame
            last_detection_frame = self.last_known_frame.get(track_id, self.last_seen[track_id])
            last_detection_timestamp = self.last_seen_time.get(track_id, 0)
            last_detection_bbox = self.last_known_bbox.get(track_id)
            two_frame_evidence = TwoFrameEvidence(
                trigger_frame_number=frame,
                trigger_timestamp=timestamp,
                last_detection_frame_number=last_detection_frame,
                last_detection_timestamp=last_detection_timestamp,
                last_detection_bbox=last_detection_bbox,
                absence_processed_frames=absence,
                absence_source_frames=list(range(last_detection_frame + 1, frame)),
                bbox_format="xyxy",
                processed_frame_size={"width": 640, "height": 360},
                source_frame_size={"width": 64, "height": 48},
            )
            return BehaviorEvent(
                event_id=str(uuid.uuid4()),
                job_id="",
                track_id=track_id,
                event_type="Tracking Lost",
                event_code="S3",
                event_label="Tracking Lost",
                event_category="system",
                start_frame=self.last_seen[track_id],
                end_frame=frame,
                start_time=self.last_seen_time.get(track_id, 0),
                end_time=timestamp,
                frame_number=frame,
                timestamp_seconds=timestamp,
                bbox=last_detection_bbox,
                observation_count=absence,
                supporting_observations=absence,
                missing_observations=absence,
                config_version=self.config.config_version,
                method_version="centroid-v1",
                explanation=f"Tracking lost: absence {absence} frames >= {self.config.tracking_lost_frames} (track cannot be recovered)",
                two_frame_evidence=two_frame_evidence,
                last_detection_frame_number=last_detection_frame,
                last_detection_timestamp=last_detection_timestamp,
                last_detection_bbox=last_detection_bbox,
                trigger_frame_number=frame,
                trigger_timestamp=timestamp,
                absence_processed_frames=absence,
            )
        return None

    def is_insufficient(self, obs: OrientationObservation) -> bool:
        return False


class InsufficientEvidenceRule:
    def check(self, obs: OrientationObservation) -> bool:
        return obs.orientation_state in ("uncertain", "unavailable") or obs.measurement_quality in (
            "low",
            "unavailable",
        )
