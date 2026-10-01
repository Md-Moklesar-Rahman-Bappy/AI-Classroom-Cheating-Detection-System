# Video Asset Failure Root Cause

## Verified Facts (from `check_video_asset.php`)

- `Storage::disk('local')->exists('video_assets/616ae582-c064-4bd8-be99-ec607153af2e.mp4')` = **true**
- `Storage::disk('local')->path('video_assets/616ae582-c064-4bd8-be99-ec607153af2e.mp4')` = `C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app/private\video_assets/616ae582-c064-4bd8-be99-ec607153af2e.mp4` (valid file inside `storage/app/private/video_assets/`)
- `file_exists(Storage::disk('local')->path(...))` = **true**
- Upload workflow path: `Storage::disk('local')->path('video_assets/'.$stored_filename)` where `$stored = Str::uuid().".".$ext` and `storeAs("video_assets", $stored, "local")` → `storage/app/private/video_assets/{uuid}.mp4`
- Worker lookup path: `Storage::disk('local')->path('video_assets/'.$videoAsset->stored_filename)` → same directory, same disk (`local` root `storage/app/private`), so **paths are consistent** (`upload workflow path` == `worker lookup path`)

## Database Lookup

- `VideoAsset::latest()->first()` → id=1, stored_filename=616ae582..., exam_session_id=1, exists on disk true
- `AnalysisJob::where('video_asset_id',1)->latest()->first()` → **None** (no job for this asset)
- `AnalysisJob::all()` → 2 jobs, both with `video_asset_id=NULL` (empty), status `failed` and `pending`, `exam_session_id=1`
- Direct DB: `SELECT id, video_asset_id FROM analysis_jobs` → `1 | NULL | recorded_video` and `2 | NULL | recorded_video`

## AiServiceClient Request Payload

- `ProcessAnalysisJob` does:
  ```php
  $filePath = Storage::disk('local')->path('video_assets/'.$videoAsset->stored_filename);
  $result = $client->createRecordedJob($filePath, $videoAsset->original_filename, $correlationId);
  // inside AiServiceClient:
  ->attach('file', file_get_contents($filePath), $originalFilename)->post('/api/v1/jobs/recorded')
  ```
- Payload is `multipart/form-data` with `file` field, `original_filename` preserved as metadata, `file_get_contents($filePath)` where `$filePath` is the absolute path from `Storage::disk('local')->path(...)` (valid, exists, true)

## Comparison

- **Upload workflow path** (in `VideoAssetController@store`):
  ```php
  $stored = Str::uuid().".".$file->getClientOriginalExtension();
  $path = $file->storeAs("video_assets", $stored, "local"); // local = storage/app/private
  $absolute = Storage::disk('local')->path($path); // storage/app/private/video_assets/{uuid}.mp4
  ```
- **Worker lookup path** (in `ProcessAnalysisJob@handle`):
  ```php
  $filePath = Storage::disk('local')->path('video_assets/'.$videoAsset->stored_filename); // same disk, same prefix
  ```
- Both use `Storage::disk('local')` with root `storage/app/private`, both use `video_assets/` prefix, so **no path mismatch**.

## Exact Failing Line

**File**: `dashboard/app/Jobs/ProcessAnalysisJob.php` **Lines 43-46** (pre-fix):

```php
$videoAsset = $job->videoAsset;
if (! $videoAsset) {
    $job->update(['status' => 'failed', 'failure_reason' => 'Video asset not found', 'failed_at' => now()]);
    return;
}
```

**Why it failed**: `$job->videoAsset` was `null` because `$job->video_asset_id` was `NULL` in `analysis_jobs` table (see DB dump above). The `video_asset_id` was `NULL` because the **Create Analysis Job form** (`resources/views/analysis-jobs/create.blade.php`) **did not contain an input for `video_asset_id`**:

```blade
<!-- BEFORE (missing) -->
<select name="exam_session_id">...</select>
<select name="source_type">...</select>
<select name="model_version_id">...</select>
<!-- no video_asset_id -->
```

The controller `AnalysisJobController@create` correctly passed `$videoAssets = VideoAsset::with('session')->latest()->get()` to the view, but the view never rendered a `<select name="video_asset_id">`, so the POST to `analysis-jobs.store` had `video_asset_id = null` (validated as `nullable`), the job was created with `video_asset_id = NULL`, and the worker's DB lookup `VideoAsset::find(null)` → `null`, hence "Video asset not found" even though the file exists on disk.

**Not a storage issue**: The file exists and is readable (`Storage::exists` true, `file_exists` true, absolute path valid), but the **database foreign key was never set** due to missing form field.

## Fix Applied

1. **View**: Added `<select name="video_asset_id">` to `analysis-jobs/create.blade.php` with `@foreach($videoAssets as $v)` and JS to require it for `recorded_video`:
   ```blade
   <div id="video_asset_group"><label>Video Asset <span class="text-danger">*</span></label><select name="video_asset_id" ...>@foreach($videoAssets as $v)<option value="{{ $v->id }}">{{ $v->original_filename }}</option>@endforeach</select></div>
   <script>/* toggle required for recorded_video */</script>
   ```

2. **Controller**: Changed validation to `video_asset_id => required_if:source_type,recorded_video|nullable|exists:video_assets,id` and filter `ModelVersion::active()->get()` for dropdown.

3. **Migration/Seeder**: Added `is_active` to `model_versions` (already done in 6.6.4) ensures at least one active model exists, so `ModelVersion::active()->get()` in `create` never empty.

4. **Logging**: Added detailed `Log::info` for `video_asset_id`, `stored_filename`, `lookup_disk`, `lookup_path`, `storage_exists`, `absolute_path`, `direct_lookup` to trace future failures (see `ProcessAnalysisJob.php` lines 42-70).

## Verification (after fix)

- `VideoAsset::active` YOLO11 Nano exists, `AnalysisJob::create` with `video_asset_id=1` → `videoAsset` relation found, `Storage::exists` true, `file_exists` true, `AiServiceClient` payload `file_get_contents($filePath)` succeeds, job progresses to `processing` not `failed`.
- Existing jobs with `video_asset_id=NULL` remain `failed` (expected, they were created without asset); new jobs succeed.

## Tests

- `tests/Feature/VideoAssetLookupTest.php` covers valid asset path, existing uploaded video, queue lookup success, missing asset failure.

## No Secrets Exposed

- All paths are within `storage/app/private`, no absolute path leaked to user, only logged server-side with redaction.
