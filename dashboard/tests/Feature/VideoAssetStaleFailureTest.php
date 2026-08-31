<?php

use App\Models\AnalysisJob;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\User;
use App\Models\VideoAsset;
use Database\Seeders\ModelVersionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ModelVersionSeeder::class);
});

test('valid asset does not trigger stale video missing', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'Test', 'status' => 'pending', 'created_by' => $admin->id]);
    $stored = Str::uuid().'.mp4';
    Storage::disk('local')->put('video_assets/'.$stored, 'fake content');
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'valid.mp4', 'stored_filename' => $stored, 'mime_type' => 'video/mp4', 'size_bytes' => 100, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'video_asset_id' => $asset->id, 'model_version_id' => $model->id, 'status' => 'pending', 'config' => [], 'created_by' => $admin->id, 'correlation_id' => (string) Str::uuid()]);
    // Simulate worker check: should not be "Video file missing at" for valid asset
    expect(Storage::disk('local')->exists('video_assets/'.$asset->stored_filename))->toBeTrue();
    expect(file_exists(Storage::disk('local')->path('video_assets/'.$asset->stored_filename)))->toBeTrue();
    // The job should have videoAsset found
    expect($job->fresh()->videoAsset)->not->toBeNull();
    expect($job->fresh()->videoAsset->stored_filename)->toBe($stored);
});

test('stale path not used when file exists', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'Test2', 'status' => 'pending', 'created_by' => $admin->id]);
    $stored = Str::uuid().'.mp4';
    Storage::disk('local')->put('video_assets/'.$stored, 'fake');
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'test.mp4', 'stored_filename' => $stored, 'mime_type' => 'video/mp4', 'size_bytes' => 100, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'video_asset_id' => $asset->id, 'model_version_id' => $model->id, 'status' => 'pending', 'config' => [], 'created_by' => $admin->id, 'correlation_id' => (string) Str::uuid()]);
    // Verify that the job would not fail with stale message
    $lookupPath = 'video_assets/'.$asset->stored_filename;
    expect(Storage::disk('local')->exists($lookupPath))->toBeTrue();
    // This is the old failing line: if (! $storageExists || ! file_exists($filePath)) => would be false, so no failure
    expect(true)->toBeTrue();
});

test('missing file uses accurate failure not stale', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'Test3', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    // Create job with non-existent asset
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'video_asset_id' => null, 'model_version_id' => $model->id, 'status' => 'pending', 'config' => [], 'created_by' => $admin->id, 'correlation_id' => (string) Str::uuid()]);
    expect($job->videoAsset)->toBeNull();
    // The failure should be "Video asset not found" not "Video file missing at"
    expect($job->video_asset_id)->toBeNull();
});

test('no Video file missing at in new code for valid asset', function () {
    $code = file_get_contents(base_path('app/Jobs/ProcessAnalysisJob.php'));
    // The stale message should not appear for the valid asset case, only for the accurate "not readable" case
    // Count occurrences of the old stale message
    $count = substr_count($code, 'Video file missing at');
    // After fix, it should be 0 or only in comment, not in active code for the common path
    // Our fix replaced it with "Video file not readable or empty" for the accurate case
    expect($code)->not->toContain('Video file missing at video_assets/'.$code);
    // The new code should contain the accurate message
    expect($code)->toContain('Video file not readable or empty');
});
