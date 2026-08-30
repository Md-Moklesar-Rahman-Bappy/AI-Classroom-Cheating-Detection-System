from abc import ABC, abstractmethod

from .models import AnalysisJob


class JobRepository(ABC):
    @abstractmethod
    def create(self, job: AnalysisJob) -> AnalysisJob: ...

    @abstractmethod
    def get(self, job_id: str) -> AnalysisJob | None: ...

    @abstractmethod
    def update(self, job: AnalysisJob) -> AnalysisJob: ...

    @abstractmethod
    def list_all(self) -> list[AnalysisJob]: ...


class InMemoryJobRepository(JobRepository):
    def __init__(self) -> None:
        self._store: dict[str, AnalysisJob] = {}

    def create(self, job: AnalysisJob) -> AnalysisJob:
        self._store[job.job_id] = job
        return job

    def get(self, job_id: str) -> AnalysisJob | None:
        return self._store.get(job_id)

    def update(self, job: AnalysisJob) -> AnalysisJob:
        self._store[job.job_id] = job
        return job

    def list_all(self) -> list[AnalysisJob]:
        return list(self._store.values())
