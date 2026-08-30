# Analysis Job Lifecycle

## Statuses
`pending | queued | processing | cancelling | cancelled | failed | completed`

## Valid Transitions
```
pending -> queued, processing, cancelled, failed
queued -> processing, cancelling, cancelled, failed
processing -> cancelling, cancelled, failed, completed
cancelling -> cancelled, failed
cancelled -> (terminal)
failed -> (terminal, retry creates new job)
completed -> (terminal)
```

Rejected transitions raise `ValueError` -> API 409 `CONFLICT`.

## Creation
- Upload validated file -> `AnalysisJob(input_path=safe_storage, original_filename, status=pending)` -> `transition(queued)` -> `job_repo.create()`.
- From existing validated path: same flow.

## Processing
- `GET /jobs/{id}` must be `queued` or `pending` else 409.
- `transition(processing)` sets `started_at`.
- Frame loop updates `frames_processed, frames_skipped, detection_invocations, progress_percent, event_count, metrics` periodically.
- Cancellation flag checked each frame: if `cancel_requested` then `processing->cancelling->cancelled`.
- Success: set `output_path, output_metadata, metrics`, `progress_percent=100`, `transition(completed)`.
- Failure: set `failure_reason, error_count`, `transition(failed)` (writer/detector/IO exceptions).

## Cancellation
- `POST /jobs/{id}/cancel`:
  - `pending/queued` -> `cancelled` immediately.
  - `processing` -> `cancelling` (next frame loop will observe flag and finish as `cancelled`).
  - `completed/failed/cancelled` -> idempotent return current status.
- API returns `{job_id, status}`.

## Retry
- `POST /jobs/{id}/retry` only from `failed` or `cancelled`; creates **new** `AnalysisJob` with same `input_path` (new `job_id`, `pending->queued`). Re-processes synchronously.
- From other statuses -> 409.
- Original failed job retained for audit.

## Progress
- `progress_percent` derived from `frame_index/total_frames` where total known, else 0 until completion (100).
- `frames_total` from `VideoMetadata.frame_count` (-1 if unknown).
- Polled via `GET /jobs/{id}`.

## Metrics & Evidence
- Metrics snapshot stored in `job.metrics` at completion; exposed via `GET /jobs/{id}/metrics`.
- Events via `GET /jobs/{id}/events`; evidence files under `evidence/{job_id}/` with limited JPGs.

## Failure Handling
- Invalid file/empty/unreadable -> 422 `VALIDATION_ERROR`.
- Writer init failure -> `RuntimeError` -> job `failed` with `failure_reason`, writer released.
- Detector not loaded -> 503 or job `failed`.
- Resources (`VideoCapture`, `VideoWriter`) released in `finally` on every exit path.
- Temporary upload files cleaned even on failure.

## Persistence (Development)
- `InMemoryJobRepository` (dict) and `InMemoryEventRepository` (list) - no DB, reset on restart. Sufficient for Phase 3; SQLite/JSON justified only if persistence required before Laravel integration.
