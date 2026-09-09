import time
from dataclasses import dataclass


@dataclass
class DedupKey:
    job_id: str
    source: str
    track_id: int
    event_type: str


class EventDeduplicator:
    def __init__(
        self,
        cooldown_frames: int = 45,
        default_cooldown_seconds: float = 5.0,
        max_entries: int = 10000,
    ):
        self.cooldown_frames = cooldown_frames
        self.cooldown_seconds = default_cooldown_seconds
        self.max_entries = max_entries
        self._last_frame: dict[tuple, int] = {}
        self._last_time: dict[tuple, float] = {}
        self.suppressed_counts: dict[str, int] = {}
        self._job_keys: dict[str, set] = {}

    def _key(self, job_id: str, source: str, track_id: int, event_type: str) -> tuple:
        return (job_id, source or "default", int(track_id), str(event_type))

    def should_suppress(
        self,
        job_id: str,
        source: str,
        track_id: int,
        event_type: str,
        frame: int,
        timestamp: float | None = None,
    ) -> bool:
        k = self._key(job_id, source, track_id, event_type)
        now = timestamp if timestamp is not None else time.monotonic()
        last_f = self._last_frame.get(k)
        if last_f is not None and (frame - last_f) < self.cooldown_frames:
            self.suppressed_counts[event_type] = self.suppressed_counts.get(event_type, 0) + 1
            return True
        if timestamp is not None:
            last_t = self._last_time.get(k)
            if last_t is not None and (now - last_t) < self.cooldown_seconds:
                self.suppressed_counts[event_type] = self.suppressed_counts.get(event_type, 0) + 1
                return True
        return False

    def record(
        self,
        job_id: str,
        source: str,
        track_id: int,
        event_type: str,
        frame: int,
        timestamp: float | None = None,
    ):
        k = self._key(job_id, source, track_id, event_type)
        now = timestamp if timestamp is not None else time.monotonic()
        self._last_frame[k] = frame
        self._last_time[k] = now
        s = self._job_keys.setdefault(job_id, set())
        s.add(k)
        if len(self._last_frame) > self.max_entries:
            oldest = next(iter(self._last_frame))
            self._last_frame.pop(oldest, None)
            self._last_time.pop(oldest, None)

    def cleanup_job(self, job_id: str):
        keys = self._job_keys.pop(job_id, set())
        for k in keys:
            self._last_frame.pop(k, None)
            self._last_time.pop(k, None)

    def clear_stale(self, max_age_frames: int = 1000, current_frame: int = 0):
        stale = [k for k, v in self._last_frame.items() if current_frame - v > max_age_frames]
        for k in stale:
            self._last_frame.pop(k, None)
            self._last_time.pop(k, None)
