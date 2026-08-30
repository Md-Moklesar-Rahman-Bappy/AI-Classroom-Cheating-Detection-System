<?php

use App\Jobs\ProcessAnalysisJob;
use App\Models\AnalysisJob;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\User;
use App\Models\VideoAsset;
use Database\Seeders\ModelVersionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ModelVersionSeeder::class);
});

test('valid asset path', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession', 'status' => 'pending', 'created_by' => $admin->id]);
    $file = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
    $this->actingAs($admin)->post(route('video-assets.store'), ['exam_session_id' => $session->id, 'video' => $file])->assertRedirect();
    $asset = VideoAsset::latest()->first();
    expect(Storage::disk('local')->exists('video_assets/'.$asset->stored_filename))->toBeTrue();
    $path = Storage::disk('local')->path('video_assets/'.$asset->stored_filename);
    expect(file_exists($path))->toBeTrue();
    expect($path)->toContain('video_assets');
    expect($path)->toContain('private');
});

test('existing uploaded video', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession2', 'status' => 'pending', 'created_by' => $admin->id]);
    $stored = Str::uuid().'.mp4';
    Storage::disk('local')->put('video_assets/'.$stored, 'fake content');
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'video.mp4', 'stored_filename' => $stored, 'mime_type' => 'video/mp4', 'size_bytes' => 1024, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    expect(Storage::disk('local')->exists('video_assets/'.$asset->stored_filename))->toBeTrue();
    expect(file_exists(Storage::disk('local')->path('video_assets/'.$asset->stored_filename)))->toBeTrue();
});

test('queue lookup success', function () {
    Queue::fake();
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession3', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    $stored = Str::uuid().'.mp4';
    Storage::disk('local')->put('video_assets/'.$stored, 'fake content');
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'video.mp4', 'stored_filename' => $stored, 'mime_type' => 'video/mp4', 'size_bytes' => 1024, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    $response = $this->actingAs($admin)->post(route('analysis-jobs.store'), ['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'video_asset_id' => $asset->id]);
    $response->assertRedirect();
    $job = AnalysisJob::latest()->first();
    expect($job->video_asset_id)->toBe($asset->id);
    expect($job->videoAsset)->not->toBeNull();
    expect($job->videoAsset->stored_filename)->toBe($stored);
    expect(Storage::disk('local')->exists('video_assets/'.$job->videoAsset->stored_filename))->toBeTrue();
    Queue::assertPushed(ProcessAnalysisJob::class, function ($job) {
        return $job->analysisJobId === AnalysisJob::latest()->first()->id;
    });
});

test('missing asset failure', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession4', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'video_asset_id' => null, 'model_version_id' => $model->id, 'status' => 'pending', 'config' => [], 'created_by' => $admin->id, 'correlation_id' => (string) Str::uuid()]);
    expect($job->videoAsset)->toBeNull();
    expect($job->video_asset_id)->toBeNull();
    // The worker's failing line is if (! $videoAsset) => Video asset not found (id=null)
    $found = VideoAsset::find($job->video_asset_id);
    expect($found)->toBeNull();
});

test('create job requires video asset for recorded_video', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession5', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    $response = $this->actingAs($admin)->post(route('analysis-jobs.store'), ['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'video_asset_id' => null]);
    $response->assertSessionHasErrors('video_asset_id');
});

test('soft delete video asset', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SoftDeleteSession', 'status' => 'pending', 'created_by' => $admin->id]);
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'soft.mp4', 'stored_filename' => 'soft.mp4', 'mime_type' => 'video/mp4', 'size_bytes' => 1024, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    expect(VideoAsset::count())->toBe(1);
    $asset->delete();
    expect(VideoAsset::count())->toBe(0);
    expect(VideoAsset::withTrashed()->count())->toBe(1);
});

test('restore soft deleted video asset', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'RestoreSession', 'status' => 'pending', 'created_by' => $admin->id]);
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'restore.mp4', 'stored_filename' => 'restore.mp4', 'mime_type' => 'video/mp4', 'size_bytes' => 1024, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    $asset->delete();
    expect(VideoAsset::withTrashed()->count())->toBe(1);
    $asset->restore();
    expect(VideoAsset::count())->toBe(1);
    expect(VideoAsset::find($asset->id))->not->toBeNull();
});

test('video asset index page does not crash with deleted_at column', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'IndexSession', 'status' => 'pending', 'created_by' => $admin->id]);
    VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'index.mp4', 'stored_filename' => 'index.mp4', 'mime_type' => 'video/mp4', 'size_bytes' => 1024, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    $response = $this->actingAs($admin)->get(route('video-assets.index'));
    $response->assertStatus(200);
});

test('create job form shows video asset dropdown', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession6', 'status' => 'pending', 'created_by' => $admin->id]);
    Storage::disk('local')->put('video_assets/test123.mp4', 'fake');
    VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'test123.mp4', 'stored_filename' => 'test123.mp4', 'mime_type' => 'video/mp4', 'size_bytes' => 1024, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    $response = $this->actingAs($admin)->get(route('analysis-jobs.create'));
    $response->assertStatus(200);
    $response->assertSee('video_asset_id');
    $response->assertSee('test123.mp4');
});
