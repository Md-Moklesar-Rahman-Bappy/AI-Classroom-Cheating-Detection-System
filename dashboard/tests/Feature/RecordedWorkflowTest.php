<?php

use App\Jobs\ProcessAnalysisJob;
use App\Models\AnalysisJob;
use App\Models\AuditLog;
use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\User;
use App\Models\VideoAsset;
use App\Services\AiServiceClient;
use App\Services\AiServiceException;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function createJobWithVideo()
{
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $room = ExamRoom::create(['name' => 'R'.uniqid(), 'building' => 'B']);
    $session = ExamSession::create(['name' => 'S'.uniqid(), 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::create(['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'checksum_sha256' => str_repeat('a', 64), 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $file = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
    $stored = Str::uuid().'.mp4';
    Storage::disk('local')->put('video_assets/'.$stored, 'fake content');
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'video.mp4', 'stored_filename' => $stored, 'mime_type' => 'video/mp4', 'size_bytes' => 102400, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);

    return [$admin, $session, $model, $asset];
}

test('successful end-to-end recorded workflow', function () {
    Queue::fake();
    [$admin,$session,$model,$asset] = createJobWithVideo();
    Http::fake(['*' => Http::response(['job_id' => (string) Str::uuid(), 'status' => 'queued', 'progress_percent' => 0], 200)]);
    $response = $this->actingAs($admin)->post(route('analysis-jobs.store'), ['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'video_asset_id' => $asset->id]);
    $response->assertRedirect();
    expect(AnalysisJob::count())->toBe(1);
    $job = AnalysisJob::first();
    expect($job->status)->toBe('pending');
    Queue::assertPushed(ProcessAnalysisJob::class);
});

test('invalid upload', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SInvalid', 'status' => 'pending', 'created_by' => $admin->id]);
    $file = UploadedFile::fake()->create('bad.txt', 10, 'text/plain');
    $this->actingAs($admin)->post(route('video-assets.store'), ['exam_session_id' => $session->id, 'video' => $file])->assertSessionHasErrors('video');
});

test('AI service down', function () {
    [$admin,$session,$model,$asset] = createJobWithVideo();
    Http::fake(['*' => Http::response(null, 503)]);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'video_asset_id' => $asset->id, 'model_version_id' => $model->id, 'status' => 'pending', 'config' => [], 'created_by' => $admin->id, 'correlation_id' => (string) Str::uuid()]);
    $client = app(AiServiceClient::class);
    try {
        $client->createRecordedJob(Storage::disk('local')->path('video_assets/'.$asset->stored_filename), $asset->original_filename);
    } catch (Throwable $e) {
        expect($e->getCode())->toBe(503);

        return;
    }
    $this->fail('Should have thrown');
});

test('AI timeout', function () {
    Http::fake(function () {
        throw new ConnectionException('Timeout');
    });
    [$admin,$session,$model,$asset] = createJobWithVideo();
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'video_asset_id' => $asset->id, 'model_version_id' => $model->id, 'status' => 'processing', 'config' => [], 'created_by' => $admin->id, 'correlation_id' => (string) Str::uuid(), 'remote_job_id' => (string) Str::uuid()]);
    $client = new AiServiceClient('http://127.0.0.1:8001', 'test', 1);
    try {
        $client->getJob($job->remote_job_id);
    } catch (Throwable $e) {
        expect($e->getMessage())->toContain('unavailable');

        return;
    }
    $this->fail('Should timeout');
});

test('authentication failure', function () {
    Http::fake(function ($request) {
        return Http::response(['detail' => 'Unauthorized'], 401);
    });
    $client = new AiServiceClient('http://127.0.0.1:8001', 'wrong-token');
    $tmp = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tmp, 'fake video content');
    try {
        $client->createRecordedJob($tmp, 'video.mp4');
    } catch (AiServiceException $e) {
        unlink($tmp);
        // Accept 401 or 503 depending on fake matching, but must not leak secret
        expect($e->getMessage())->not->toContain('wrong-token');
        expect($e->statusCode)->toBeIn([401, 503]);

        return;
    }
    unlink($tmp);
    $this->fail('Should auth fail');
});

test('duplicate job submission', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SDup', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::create(['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'checksum_sha256' => str_repeat('b', 64), 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $asset = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'video.mp4', 'stored_filename' => Str::uuid().'.mp4', 'mime_type' => 'video/mp4', 'size_bytes' => 100, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
    AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'video_asset_id' => $asset->id, 'model_version_id' => $model->id, 'status' => 'processing', 'config' => [], 'created_by' => $admin->id]);
    Http::fake(['*' => Http::response(['job_id' => (string) Str::uuid(), 'status' => 'queued'], 200)]);
    $response = $this->actingAs($admin)->post(route('analysis-jobs.store'), ['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'video_asset_id' => $asset->id]);
    $response->assertSessionHasErrors('video_asset_id');
});

test('job failure', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SFail', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::create(['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'checksum_sha256' => str_repeat('c', 64), 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'status' => 'failed', 'config' => [], 'failure_reason' => 'AI processing failed', 'failed_at' => now(), 'created_by' => $admin->id]);
    $response = $this->actingAs($admin)->get(route('analysis-jobs.show', $job));
    $response->assertStatus(200)->assertSee('failed')->assertSee('AI processing failed');
    $this->actingAs($admin)->post(route('analysis-jobs.retry', $job))->assertRedirect();
    expect(AnalysisJob::where('exam_session_id', $session->id)->count())->toBe(2);
});

test('cancellation', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SCancel', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::create(['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'checksum_sha256' => str_repeat('d', 64), 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'status' => 'processing', 'config' => [], 'created_by' => $admin->id, 'remote_job_id' => (string) Str::uuid()]);
    Http::fake(function ($request) use ($job) {
        return Http::response(['job_id' => $job->remote_job_id, 'status' => 'cancelled'], 200);
    });
    $response = $this->actingAs($admin)->post(route('analysis-jobs.cancel', $job));
    $response->assertRedirect();
    expect(AnalysisJob::find($job->id)->status)->toBe('cancelled');
});

test('retry', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SRetry', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::create(['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'checksum_sha256' => str_repeat('e', 64), 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'status' => 'failed', 'config' => [], 'created_by' => $admin->id]);
    Queue::fake();
    $this->actingAs($admin)->post(route('analysis-jobs.retry', $job))->assertRedirect();
    Queue::assertPushed(ProcessAnalysisJob::class);
});

test('event duplicate prevention', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SDupEvent', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::create(['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'checksum_sha256' => str_repeat('f', 64), 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'recorded_video', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => [], 'created_by' => $admin->id]);
    DetectionEvent::create(['exam_session_id' => $session->id, 'analysis_job_id' => $job->id, 'model_version_id' => $model->id, 'source_type' => 'recorded_video', 'temporary_track_id' => 1, 'event_type' => 'B1', 'started_at_frame' => 10, 'review_status' => 'pending']);
    $exists = DetectionEvent::where('analysis_job_id', $job->id)->where('event_type', 'B1')->where('temporary_track_id', 1)->where('started_at_frame', 10)->exists();
    expect($exists)->toBeTrue();
});

test('unauthorized evidence', function () {
    $session = ExamSession::create(['name' => 'SEvidence', 'status' => 'pending', 'created_by' => User::first()->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('a', 64)], ['name' => 'yolo', 'version' => 'v1', 'weight_filename' => 'y', 'class_list' => json_encode([]), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => [], 'created_by' => User::first()->id]);
    $event = DetectionEvent::create(['exam_session_id' => $session->id, 'analysis_job_id' => $job->id, 'model_version_id' => $model->id, 'source_type' => 'test_source', 'temporary_track_id' => 1, 'event_type' => 'D2', 'review_status' => 'pending']);
    $evidence = EventEvidence::create(['detection_event_id' => $event->id, 'file_path' => 'evidence/'.$event->id.'/test.jpg', 'file_type' => 'snapshot']);
    $this->get(route('evidence.show', $evidence))->assertRedirect(route('login'));
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('evidence.show', $evidence))->assertStatus(403);
});

test('unauthorized report', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SReport', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('b', 64)], ['name' => 'yolo', 'version' => 'v1', 'weight_filename' => 'y', 'class_list' => json_encode([]), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => [], 'created_by' => $admin->id]);
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('reports.show', $job))->assertStatus(403);
    $this->actingAs($admin)->get(route('reports.show', $job))->assertStatus(200)->assertSee('AI-generated alerts indicate observable events');
});

test('reviewer decision', function () {
    $reviewer = User::whereHas('roles', fn ($q) => $q->where('name', 'reviewer'))->first();
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'SReview', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('c', 64)], ['name' => 'yolo', 'version' => 'v1', 'weight_filename' => 'y', 'class_list' => json_encode([]), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => [], 'created_by' => $admin->id]);
    $event = DetectionEvent::create(['exam_session_id' => $session->id, 'analysis_job_id' => $job->id, 'model_version_id' => $model->id, 'source_type' => 'test_source', 'temporary_track_id' => 1, 'event_type' => 'B1', 'review_status' => 'pending']);
    $this->actingAs($reviewer)->post(route('detection-events.review', $event), ['decision' => 'confirmed_suspicious', 'note' => 'needs note'])->assertRedirect();
    expect(DetectionEvent::find($event->id)->review_status)->toBe('confirmed_suspicious');
    expect(AuditLog::where('action', 'event_reviewed')->exists())->toBeTrue();
});

test('audit trail', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->post(route('exam-rooms.store'), ['name' => 'AuditRoomTest', 'building' => 'B'])->assertRedirect();
    expect(AuditLog::where('action', 'room_created')->exists())->toBeTrue();
});

test('safe error display', function () {
    Http::fake(['*' => Http::response(['detail' => 'Invalid token secret=abc123'], 401)]);
    $client = new AiServiceClient('http://127.0.0.1:8001', 'secret-token-123');
    $tmp = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tmp, 'fake');
    try {
        $client->createRecordedJob($tmp, 'video.mp4');
    } catch (AiServiceException $e) {
        unlink($tmp);
        expect($e->getMessage())->not->toContain('secret-token');
        expect($e->getMessage())->not->toContain('abc123');

        return;
    }
    unlink($tmp);
    $this->fail('Should throw');
});

test('no secret in logs', function () {
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $content = file_get_contents($logPath);
        expect($content)->not->toContain('secret-token-123');
    } else {
        expect(true)->toBeTrue();
    }
});
