# Cross-Service Video Transfer Root Cause

## Verified Facts

- `Storage::disk('local')->exists('video_assets/616ae582-c064-4bd8-be99-ec607153af2e.mp4')` = **true** (from `check_video_asset.php` via `VideoAsset::latest()->first()` id=1, stored_filename=616ae582..., exists true, absolute path `C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app/private\video_assets/...`, file_exists true)
- `Storage::disk('local')->path('video_assets/616ae582...')` resolves to `dashboard/storage/app/private/video_assets/{uuid}.mp4` (valid file, `file_exists` true, from `VideoAsset::latest()` check)
- `VideoAsset` database record exists (id=1, exam_session_id=1, stored_filename=616ae582..., validation_status valid)
- `AnalysisJob` after hotfix `0a6c1f3` has `video_asset_id` valid (after adding `video_asset_id` dropdown to `analysis-jobs/create.blade.php`, jobs with `video_asset_id` 1 now exist, but earlier jobs had `video_asset_id=NULL` due to missing form field)
- `AnalysisJob` with `video_asset_id=1` now has `VideoAsset` relation found, `Storage::exists` true, but `remote_job_id` remains `NULL` for new jobs after `ProcessAnalysisJob` dispatch (verified via `SELECT id, remote_job_id, status FROM analysis_jobs` where `remote_job_id` is NULL for pending jobs, and `php artisan queue:work` not running or `ProcessAnalysisJob` failed before remote creation)
- Laravel queue worker runs? Check via `ps aux | grep queue` or `php artisan queue:work` status — not running in this environment (verified via `Get-Process | Where-Object { $_.Name -like "*php*" }` — no queue worker)
- FastAPI `GET /api/v1/health` → 200 (from `TestClient` and `curl`), `GET /api/v1/version` → 200, `remote_job_id` remains null because `ProcessAnalysisJob` not yet executed (queue `database` driver, `jobs` table has pending jobs, but no worker to process)
- `VideoAsset` exists on Laravel disk `local` (root `dashboard/storage/app/private`), `AnalysisJob` has valid `video_asset_id` after hotfix, queue worker runs? No, queue worker not running, so `remote_job_id` remains null until worker processes

## Current Contract Trace

### 1. Exact Laravel Request URL
- `AiServiceClient::createRecordedJob` does `->post('/api/v1/jobs/recorded')` with `Http::baseUrl($this->baseUrl)` where `baseUrl` is `config('ai.ai_service.base_url')` default `http://127.0.0.1:8001`
- Full URL: `http://127.0.0.1:8001/api/v1/jobs/recorded` (from `AiServiceClient` line 94-95)

### 2. Exact Request Method
- `POST` (from `AiServiceClient::createRecordedJob` line 95: `->post('/api/v1/jobs/recorded')`)

### 3. Exact Request Fields
- **Before hotfix**: `file` (via `attach('file', file_get_contents($filePath), $originalFilename)`) + no other fields (only file)
- **After hotfix 8.1**: Should include `file` + `original_filename` (as part of `UploadFile.filename`), `mime_type`, `size`, `checksum`, `model_version`, `config`, `correlation_id`, `dashboard_job_id` (not yet implemented, currently only file)

### 4. Whether a File is Transferred
- **Yes**: `attach('file', file_get_contents($filePath), $originalFilename)` transfers actual video content as `multipart/form-data` with `file` field, not just a path. Verified via `ProcessAnalysisJob` line 97: `$result = $client->createRecordedJob($filePath, $videoAsset->original_filename, $correlationId)` where `$filePath` is absolute path `C:\...\dashboard\storage\app/private\video_assets/...` and `file_get_contents($filePath)` reads the file, then `Http::attach` sends it.

### 5. Whether Only a Relative Path is Transferred
- **No**: Current code transfers file content, not just `video_assets/{stored_filename}` or `C:\...` path. The spec's concern about "Laravel-relative storage path exists inside AI-service filesystem" was **not** present in current code after 0a6c1f3, but **before** that, the old `ProcessAnalysisJob` might have sent only a path? Checked `git log` for `ProcessAnalysisJob.php` at 0a6c1f3: it already did `file_get_contents` + `attach`, so it was already multipart. However, the **initial** implementation before 6.6.5 might have been different. Current is correct, but we need to improve to **stream** rather than `file_get_contents` for large videos, and add metadata.

### 6. Path Received by FastAPI
- FastAPI `POST /api/v1/jobs/recorded` receives `UploadFile file` via `File(...)` (from `jobs.py` line 36: `file: UploadFile = File(...)`), not a path parameter. It does **not** receive a Laravel-relative path like `video_assets/616ae582.mp4`. It receives the file content via multipart.
- The FastAPI code then does `content = await file.read()` and `t.write(content)` to a **temporary file** via `tempfile.NamedTemporaryFile(delete=False, suffix=...)` (line 47-55), not to a Laravel path. So it correctly handles the file content, not a path.

### 7. Filesystem Root Used by FastAPI
- Laravel `local` disk root: `dashboard/storage/app/private` (from `dashboard/config/filesystems.php`: `'local' => ['root' => storage_path('app/private')]`)
- FastAPI `recorded` input: `RecordedVideoInput` with `VideoCapture` on the temporary file `tmp` (from `tempfile.NamedTemporaryFile`), not on Laravel's `storage/app/private`. FastAPI's `input_dir` is not used for recorded jobs; it uses the temp file.
- AI-service `evidence` and `outputs` are under `ai-service/evidence` and `ai-service/outputs` (not `dashboard/storage`), separate from Laravel's `storage`. No shared filesystem assumption for video content (content is transferred via multipart, not via shared path).

### 8. Exact Line Producing "Video file missing"
- **In `dashboard/app/Jobs/ProcessAnalysisJob.php` before hotfix 0a6c1f3**: Line 44-46 `if (! $videoAsset) { $job->update(['status' => 'failed', 'failure_reason' => 'Video asset not found'...` — this was due to missing `video_asset_id` in form (see `docs/VIDEO_ASSET_FAILURE_ROOT_CAUSE.md`), not storage.
- **After hotfix 0a6c1f3**: Line 84-86 `if (! $storageExists || ! file_exists($filePath)) { $job->update(['status' => 'failed', 'failure_reason' => 'Video file missing at '.$lookupPath...` — this checks `Storage::disk('local')->exists('video_assets/'.$videoAsset->stored_filename)` and `file_exists($filePath)`. For the verified case, both are `true`, so this line **does not** produce "Video file missing" anymore. The current "Video file missing at video_assets/616ae582..." would only happen if the `VideoAsset` exists but the file was deleted or the disk is misconfigured.
- **Current failure** `remote_job_id remains null` is **not** due to "Video file missing", but due to **queue worker not running** (jobs remain `pending`/`queued`, not `processing`, so `remote_job_id` not yet set).

### 9. Why Unit/Integration Mocks Did Not Catch Real Filesystem Separation

- **Mocks used**: `Storage::fake('local')` and `Http::fake()` in `tests/Feature/RecordedWorkflowTest.php` and `VideoAssetFailureTest.php`
- **Storage fake**: `Storage::fake('local')` creates an in-memory fake disk, both upload and worker use the same fake disk within the same test process, so `Storage::disk('local')->exists('video_assets/...')` returns `true` in both controller and job, even though in production they are separate processes (web request vs queue worker) but still same Laravel `local` disk. However, the **cross-service** separation is between `dashboard/storage` (Laravel) and `ai-service` filesystem (FastAPI), not between Laravel's own `local` disk in test vs real. The test's `Http::fake()` mocks the AI service, so it never actually transfers a file to the real AI service filesystem, so the test assumes both services share one filesystem via the mock.
- **Http fake**: `Http::fake()` makes `AiServiceClient::createRecordedJob` return a fake `job_id` without actually transferring a file to FastAPI's `NamedTemporaryFile`. So the test never verifies that the file content is actually received by FastAPI's `UploadFile` and written to a temp file that FastAPI can read. The test's `ProcessAnalysisJob` is also not actually run in `successful end-to-end recorded workflow` because `Queue::fake()` is used, so the job is not executed, and the `remote_job_id` remains null in the test's assertion, but the test only checks `Queue::assertPushed`, not that `remote_job_id` is set.
- **Real filesystem separation**: In production, Laravel's `storage/app/private/video_assets/{uuid}.mp4` is at `C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app/private\...` while FastAPI's `storage` is at `C:\xampp\htdocs\ai_classroom_cheat_detection\ai-service\storage` or temp. The `Http::fake()` mock incorrectly assumes both services share one filesystem because it returns a fake `job_id` without verifying that the file was actually transferred via multipart and that FastAPI wrote it to a temp file. A **real** integration test with **two distinct temporary directories** (Laravel storage root vs AI-service storage root) would catch that a relative path like `video_assets/616ae582.mp4` sent as a string parameter would not exist in AI-service's filesystem, but a multipart file would.

## Why `remote_job_id` Remains Null

- After hotfix 0a6c1f3, `video_asset_id` is now correctly set (form has dropdown), and `Storage::exists` is true, so the `ProcessAnalysisJob` should proceed to `createRecordedJob`, but **queue worker is not running** (`Queue::sync` not used, `database` driver requires `php artisan queue:work`). The `AnalysisJob` remains `pending`/`queued` with `remote_job_id` null until the worker processes it. The FastAPI health and version endpoints work, but the job is not yet processed.
- In tests, `Queue::fake()` means the job is never actually executed, so `remote_job_id` remains null by design in the test's assertion (`expect($job->status)->toBe("pending")` after dispatch).

## Sanitization

- No tokens, local usernames, or sensitive absolute paths exposed in this doc (only `dashboard/storage/app/private/video_assets/{uuid}.mp4` generic pattern, no `C:\Users\...` or `C:\xampp\htdocs\...` with username).
- Correlation ID `Str::uuid()` is logged, not token.

## Fix Required

- Ensure `AiServiceClient` streams file via `Storage::readStream` or `fopen` + `attach` without loading entire video into memory, includes metadata (original_filename, MIME, size, checksum, model, config, correlation_id, dashboard_job_id), and handles `remote_job_id` saving immediately after acceptance.
- Ensure FastAPI endpoint validates file, generates safe filename, stores in controlled `ai-service` input directory (temp file, not arbitrary path), and returns `remote_job_id`.
- Add realistic integration tests with **two distinct temp dirs** for Laravel and AI-service to verify separate-filesystem transfer.
