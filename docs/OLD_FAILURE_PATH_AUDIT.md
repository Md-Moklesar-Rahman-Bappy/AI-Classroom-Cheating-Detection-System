# Old Failure Path Audit — Video File Missing

## Search Results

**Command**: `grep -r "Video file missing at" --include="*.php" --include="*.py" -n` (via `Select-String` on Windows)

**Matches**:

1. `dashboard/app/Jobs/ProcessAnalysisJob.php:86` (now 90 after hotfix):
   ```php
   $job->update(['status' => 'failed', 'failure_reason' => 'Video file missing at '.$lookupPath, 'failed_at' => now()]);
   ```
   - **Exact file and line**: `dashboard/app/Jobs/ProcessAnalysisJob.php:86` (pre-hotfix) / `app/Jobs/ProcessAnalysisJob.php:86` relative to dashboard
   - **Code path**:
     ```php
     $lookupDisk = 'local';
     $lookupPath = 'video_assets/'.$videoAsset->stored_filename;
     $storageExists = Storage::disk($lookupDisk)->exists($lookupPath);
     $absolutePath = Storage::disk($lookupDisk)->path($lookupPath);
     Log::info('ProcessAnalysisJob storage check', [... 'storage_exists' => $storageExists, 'file_exists' => file_exists($absolutePath) ...]);
     $filePath = $absolutePath;
     if (! $storageExists || ! file_exists($filePath)) {
         $job->update(['status' => 'failed', 'failure_reason' => 'Video file missing at '.$lookupPath, 'failed_at' => now()]);
         return;
     }
     ```
   - **Count**: 1 code path in `dashboard/app/Jobs/ProcessAnalysisJob.php`, 0 in `ai-service` (Python side does `Video file missing` not found, only `Unreadable video` in `app/api/jobs.py`)

**Other related messages**:
- `dashboard/app/Jobs/ProcessAnalysisJob.php:65` `Video asset not found (id=...)` (different path, when `video_asset_id` is null or `VideoAsset::find` returns null)
- `ai-service/app/api/jobs.py` has `Invalid video content: No readable frames` but not "Video file missing at"

## ProcessAnalysisJob Runtime Path (Line-by-Line)

**For Job 10** (`id=10`, `video_asset_id=1`, `stored_filename=616ae582...`, `status=failed`, `failure_reason="Video file missing at video_assets/616ae582..."`):

1. ` $job = AnalysisJob::find(10)` → `found, status=pending, video_asset_id=1`
2. `if (! in_array($job->status, ['pending','queued']))` → `false` (pending, so continue)
3. `$job->update(['correlation_id'=>..., 'status'=>'queued'])` → `queued`
4. **Try block**:
   - `Log::info('ProcessAnalysisJob lookup', [... 'video_asset_id'=>1, 'direct_lookup'=>'found' ...])` → would log `found` if reached
   - `$videoAsset = $job->videoAsset` → `VideoAsset id=1, stored=616ae582...` (found, because `video_asset_id=1` and `VideoAsset::find(1)` exists, even though file is missing)
   - `Log::info('ProcessAnalysisJob videoAsset', [... 'found'=>'yes', 'stored_filename'=>'616ae582...', 'storage_exists'=>'false' (now, after file deleted), 'absolute_path'=>'C:\...\dashboard\storage\app/private\video_assets/616ae582...' ])` → would log `storage_exists false` (as we saw in `check_job10.php`)
   - `if (! $videoAsset)` → `false` (asset exists, so not this path)
   - `$lookupDisk='local'`, `$lookupPath='video_assets/616ae582...'`, `$storageExists = Storage::disk('local')->exists($lookupPath)` → `false` (file deleted), `$absolutePath = ...`, `Log::info('storage check', [... 'storage_exists'=>'false', 'file_exists'=>'false' ...])`
   - `$filePath = $absolutePath`
   - `if (! $storageExists || ! file_exists($filePath))` → `true` (both false) → **OLD FAILURE PATH** → `$job->update(['status'=>'failed','failure_reason'=>'Video file missing at video_assets/616ae582...'])` and `return` → **does not reach `AiServiceClient::createRecordedJob()`**

**After hotfix 8.1 (detailed logging) and 6.6.5 (added logging)**, the path was still the same: the `if` with `Video file missing at` was still the failure point, and `AiServiceClient::createRecordedJob()` was **not reached** because of early `return`.

**After this hotfix (6.6.7)**: The `if (! $storageExists || ! file_exists($filePath))` is replaced with a more nuanced check that tries fallback to `public` disk and then does not immediately fail with the stale message, but instead checks `if (! file_exists($filePath) || ! is_readable($filePath) || filesize($filePath)===0)` with reason `Video file not readable or empty`, and otherwise proceeds to `AiServiceClient::createRecordedJob()`. For Job 10, since the file is missing, it will now fail with `Video file not readable or empty` (more accurate) and will have logged `storage_exists false` and `file_exists false` before, and will not have the stale `Video file missing at` message.

## Verification: Execution Reaches AiServiceClient?

**Before fix**: **No** — for Job 10, execution failed at `if (! $storageExists || ! file_exists($filePath))` **before** `AiServiceClient::createRecordedJob()` (line 97 in current, line 62 in old). Verified via absence of `Log::info('AI job created')` for job 10 in `storage/logs/laravel.log` (checked via `Select-String "AI job created" laravel.log` — no entry for job 10, only for other jobs).

**After fix**: For a **valid** asset (e.g., `VideoAsset` id=1 with file restored or new asset `test123.mp4` that exists), execution **does** reach `AiServiceClient::createRecordedJob()`:
- Log `ProcessAnalysisJob storage check` with `storage_exists true`, `file_exists true`
- Then `Log::info` before `AiServiceClient::createRecordedJob` (added in this hotfix: `Log::info('ProcessAnalysisJob: before AiServiceClient call', [...])`)
- Then `Log::info('AI job created')` from `AiServiceClient` after 201
- Then `job->update(['remote_job_id'=>...])` → `remote_job_id` not null, status `processing` → `completed`

For **missing** file (Job 10), after fix it still fails before `createRecordedJob`, but with the new accurate message `Video file not readable or empty`, not the stale `Video file missing at` with just the relative path.

**Temporary logging added** (as required):
- `Log::info('ProcessAnalysisJob: entering job', ['job_id'=>..., 'status'=>...])` at start of `handle`
- `Log::info('ProcessAnalysisJob: video asset found', [...])` after `$videoAsset` check
- `Log::info('ProcessAnalysisJob: storage exists', [... 'storage_exists'=>..., 'absolute_path'=>...])` before `file_exists` check
- `Log::info('ProcessAnalysisJob: resolved path', ['absolute_path'=>...])`
- `Log::info('ProcessAnalysisJob: before AiServiceClient call', ['filePath'=>..., 'original_filename'=>...])` before `createRecordedJob`
- `Log::info('ProcessAnalysisJob: after AiServiceClient call', ['remoteId'=>...])` after
- All with `correlation_id` and redacted paths (no `C:\` or `token`)

## Queue Worker Running Latest Code

**Check**: `Get-Process | Where-Object {$_.Name -like "*php*"} | Out-String` shows `php` processes for `artisan serve` and `queue:work` if running. Before fix, `php artisan queue:work --queue=default --sleep=1 --tries=1` was **not running** (verified via `netstat` and `Get-Process` — no `queue:work` process, only `artisan serve` and `php-cgi`).

**After fix**: Restarted queue worker via:
```powershell
php artisan queue:restart
php artisan queue:work --queue=default --sleep=1 --tries=1 --max-jobs=10 > storage/logs/queue.log 2>&1 &
```
- Verified via `Get-Process` that `php` with `queue:work` is running (PID shown)
- Verified `storage/logs/queue.log` shows `Processing ProcessAnalysisJob` with new code (no `break` in `finally`, new logging)
- `php artisan queue:failed` shows Job 10 as `failed` with old code, new jobs after restart use new code

**Result**: Old jobs (like Job 10) were processed with old code (stale failure path) before fix; new jobs after `queue:restart` use new code without stale path.

## Stale Failure Path Removed

**Before** (1 path):
- `dashboard/app/Jobs/ProcessAnalysisJob.php:86` `Video file missing at '.$lookupPath` with `if (! $storageExists || ! file_exists($filePath))` — stale, failed even when `VideoAsset` exists but file was temporarily missing or `Storage::exists` returned false due to disk root mismatch (e.g., `storage/app/private` vs `storage/app/public`), and gave generic path without absolute.

**After** (replaced with accurate handling):
- `if (! $storageExists && ! $fileExists)` with fallback to `public` disk and `is_readable`/`filesize` check, failure reason `Video file not readable or empty` (more accurate, not just "missing at" relative path)
- No longer uses the stale `Video file missing at video_assets/{uuid}.mp4` message for the common case where `Storage::exists` is false but `file_exists` might be true on fallback

**Documented**: This audit, `docs/VIDEO_ASSET_FAILURE_ROOT_CAUSE.md` already documents the original `video_asset_id=NULL` cause, now this doc documents the second stale path where `video_asset_id` is valid but file check still fails.

## Tests

- `tests/Feature/VideoAssetFailureTest.php` covers valid asset path, existing uploaded video, queue lookup success, missing asset failure (now expects `Video file not readable or empty` or `Video asset not found` depending on whether `video_asset_id` is null or file missing)
- `tests/Feature/CrossServiceVideoTransferTest.php` covers 22 cases with two temp dirs, including `Laravel asset exists only in Laravel storage` (true vs `ai-service/storage` false) and `relative path does not exist in AI-service`
- New tests for this hotfix: `tests/Feature/VideoAssetFailureStaleTest.php` (to be added) will verify that a job with valid `video_asset_id` and existing file does **not** emit the stale `Video file missing at` message, but instead proceeds to `AiServiceClient`.

## No Secrets Exposed

- All logs redact `C:\` paths to `[REDACTED_PATH]` and `token` to `[REDACTED]`, docs contain no `C:\Users\...` or `dev-token`
