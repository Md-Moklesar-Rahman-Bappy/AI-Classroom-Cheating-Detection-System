from abc import ABC, abstractmethod

from .models import DetectionEvent


class EventRepository(ABC):
    @abstractmethod
    def add(self, event: DetectionEvent) -> DetectionEvent: ...

    @abstractmethod
    def list_by_job(self, job_id: str) -> list[DetectionEvent]: ...

    @abstractmethod
    def get(self, event_id: str) -> DetectionEvent | None: ...


class InMemoryEventRepository(EventRepository):
    def __init__(self) -> None:
        self._events: list[DetectionEvent] = []

    def add(self, event: DetectionEvent) -> DetectionEvent:
        self._events.append(event)
        return event

    def list_by_job(self, job_id: str) -> list[DetectionEvent]:
        return [e for e in self._events if e.job_id == job_id]

    def get(self, event_id: str) -> DetectionEvent | None:
        for e in self._events:
            if e.event_id == event_id:
                return e
        return None
