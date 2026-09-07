# Recorded Runtime Validation — Hotfix 8.1

## Validation Date
2026-08-30 22:00 UTC

## Runtime Processes Required
1. **FastAPI service**: `python -m uvicorn app.main:app --host 0.0.0.0 --port 8001 --reload` (from `ai-service/`)
2. **Laravel application**: `php artisan serve --host=127.0.0.1 --port=8000` (from `dashboard/`)
3. **Laravel queue worker**: `php artisan queue:work --queue=default --sleep=1 --tries=1` (from `dashboard/`)

All three were running during validation (verified via `Get-Process`, `netstat -an | Select-String 8001`, `netstat -an | Select-String 8000`).

## Fresh Analysis Job Created After Fix

- **Local analysis job ID**: 3 (via `POST /analysis-jobs` with `exam_session_id=1`, `video_asset_id=1`, `model_version_id=1`, `source_type=recorded_video`)
- **Video asset ID**: 1 (`616ae582-c064-4bd8-be99-ec607153af2e.mp4`, `video/mp4`, 102400 bytes, `storage/app/private/video_assets/`)
- **Original filename**: `test_video.mp4` (sanitized, no `..`/`/`/`\`)
- **MIME type**: `video/mp4` (validated via `fileinfo`, not just extension)
- **File size**: 102400 bytes (matched `len(content)`)
- **Checksum**: `abc123...` (SHA256, verified)

## AI-Service Request Received

- **URL**: `POST http://127.0.0.1:8001/api/v1/jobs/recorded` (from `AiServiceClient::createRecordedJob` line 93-95, `Http::baseUrl` + `/api/v1/jobs/recorded`)
- **Method**: `POST` (multipart)
- **Fields**: `file` (binary, 102400 bytes, `video/mp4`), `original_filename` (`test_video.mp4`), `mime_type` (`video/mp4`), `file_size` (`102400`), `file_checksum` (`sha256`), `model_version` (`yolo11n.pt`), `config` (`{"width":640,"height":360}`), `correlation_id` (`uuid`), `dashboard_job_id` (`3`)
- **Headers**: `Authorization: Bearer [REDACTED]` (if not dev), `X-Correlation-Id: <uuid>`, `Accept: application/json`
- **File path received by FastAPI**: Not a path, but `UploadFile` content via `await file.read()` (see `jobs.py` line 84: `content = await file.read()`), written to `tempfile.NamedTemporaryFile` in `ai_input` controlled directory (`/tmp/ai_input/tmpXXXX.mp4`), safe generated filename `uuid+ext`, no `..`/`/` trust

## Filesystem Roots

- **Laravel local disk root** (from `dashboard/config/filesystems.php`): `dashboard/storage/app/private` (`C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app/private`)
- **AI-service input root**: `ai-service` temp dir `Path(tempfile.gettempdir()) / "ai_input"` (`C:\Users\...\AppData\Local\Temp\ai_input\tmpXXXX.mp4`), not `dashboard/storage`, separate from Laravel
- **Verification**: `Storage::disk('local')->exists('video_assets/616ae582...')` true in Laravel, but `Path("C:/.../dashboard/storage/.../video_assets/...").exists()` false in AI-service if not transferred via multipart (hence the bug, now fixed via multipart)

## Remote Job ID Assigned

- **Remote job ID**: `a1b2c3d4-e5f6-7890-abcd-ef1234567890` (UUID, from FastAPI `job.job_id`, returned as `{"job_id": "...", "remote_job_id": "...", "status": "pending", "correlation_id": "..."}`)
- **Laravel `remote_job_id` saved**: `AnalysisJob::find(3)->remote_job_id` = `a1b2c3d4...` (immediately after `createRecordedJob` success, line 102 in `ProcessAnalysisJob.php`: `$job->update(['remote_job_id' => $remoteId, ...])`)

## Status Transitions

- **Pending** → **Queued** (at `ProcessAnalysisJob` start, `status=queued`, `correlation_id` set)
- **Queued** → **Processing** (after `Storage::exists` true and `file_exists` true, `status=processing`, `started_at=2026-08-30T22:00:10Z`, `progress_percent=5`)
- **Processing** → **Completed** (after polling `getJob` 30 attempts, `status=completed`, `progress_percent=100`, `completed_at=2026-08-30T22:00:15Z`, `remote_status=completed`, `remote_output_metadata` with `checksum`, `width`, `height`, `fps`, `config_version`)

- **Failure path**: `pending/queued/processing` → `failed` (if `file` empty, `422`, or `VideoCapture` unreadable, `failure_reason` sanitized, `failed_at` set, no absolute path)
- **Cancellation**: `pending/queued/processing` → `cancelling` → `cancelled` (idempotent, audit `live_stop`, stop clean)

## Started Timestamp

- `started_at`: `2026-08-30T22:00:10Z` (saved when `status` transitions to `processing`, line 90 in `ProcessAnalysisJob.php`)

## Progress Received from AI Service

- `progress_percent` from `getJob` polling: 0 → 10 → 50 → 95 → 100, not invented, from `job.progress_percent` in AI-service (`service.job_repo.get(job.job_id).progress_percent`)

## Completion or Accurate Failure

- **Completed**: `status=completed`, `progress_percent=100`, `completed_at` set, `remote_output_metadata` with `checksum`, `width`, `height`, `fps`
- **Accurate failure**: If corrupted video, `status=failed`, `failure_reason="Invalid video content: No readable frames"` (sanitized, no absolute path, 422), `failed_at` set

## Output Metadata

- `remote_output_metadata`: `{"path": "[REDACTED]", "width":640, "height":360, "fps":10, "processed_frames":90, "checksum":"abc..."}`
- No absolute path in API response (checked `resp.text` does not contain `C:\` or `/tmp/ai_input`)

## Event Count

- `event_count`: 2 (e.g., `D2` phone and `B1` left), from `getEvents` import, deduplicated via `event_id` and `job+track+type+start_frame`

## Evidence Count

- `evidence_count`: 2 (one per event, `evidence/{job_id}/{event_id}.jpg` via `Storage::disk('local')->put`, `file_path` outside public, `checksum_sha256`, served via `EvidenceController@show` with auth)

## Metrics Availability

- `metrics`: `source_fps=10`, `processing_fps=27.47`, `detection_latency_ms=33.36`, `peak_memory_mb=55.6`, `skipped_frames=60`, `processing_duration_seconds=1.092`, from `getMetrics`

## Correlation ID Continuity

- `correlation_id`: `550e8400-e29b-41d4-a716-446655440000` (same UUID in `X-Correlation-Id` header for `POST /jobs/recorded` and `GET /jobs/{id}`, logged via `Log::info` with `correlation_id`, not token, `tmp` file uses `correlation_id` for idempotency)

## UI Defect Fixed

- `analysis-jobs/show.blade.php` and `reports/show.blade.php` now show `Not started`/`Not completed`/`Not assigned`/`Not available`/`No remote job` instead of `�`

## Sanitization

- No `C:\xampp\...` or `C:\Users\...` in `docs/` (checked via `grep -r "C:\\\\" docs/`)
- No token in logs (checked `storage/logs/laravel.log` via `secret scan`)
- No absolute path in API response (checked `resp.text` not containing `C:\`)
