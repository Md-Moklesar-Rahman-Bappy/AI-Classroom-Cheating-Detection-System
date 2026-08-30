<?php

use App\Jobs\ProcessAnalysisJob;
use App\Models\AnalysisJob;
use App\Models\AuditLog;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\User;
use App\Models\VideoAsset;
use Database\Seeders\ModelVersionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ModelVersionSeeder::class);
});

function createJobForTest(string $status = 'pending', ?int $videoAssetId = null): AnalysisJob
{
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession'.uniqid(), 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    if ($videoAssetId === null) {
        $stored = Str::uuid().'.mp4';
        Storage::disk('local')->put('video_assets/'.$stored, 'fake');
        $va = VideoAsset::create(['exam_session_id' => $session->id, 'original_filename' => 'test.mp4', 'stored_filename' => $stored, 'mime_type' => 'video/mp4', 'size_bytes' => 100, 'validation_status' => 'valid', 'uploaded_by' => $admin->id]);
        $videoAssetId = $va->id;
    }

    return AnalysisJob::create([
        'exam_session_id' => $session->id,
        'source_type' => 'test_source',
        'video_asset_id' => $videoAssetId,
        'model_version_id' => $model->id,
        'status' => $status,
        'config' => [],
        'created_by' => $admin->id,
        'correlation_id' => (string) Str::uuid(),
    ]);
}

test('delete soft deletes and audits', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $job = createJobForTest('pending');
    $this->actingAs($admin)->delete(route('analysis-jobs.destroy', $job))->assertRedirect();
    expect(AnalysisJob::withTrashed()->find($job->id)->deleted_at)->not->toBeNull();
    expect(AnalysisJob::find($job->id))->toBeNull();
    expect(AuditLog::where('action', 'job_deleted')->exists())->toBeTrue();
});

test('retry creates new job and audits', function () {
    Queue::fake();
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $failed = createJobForTest('failed');
    $this->actingAs($admin)->post(route('analysis-jobs.retry', $failed))->assertRedirect();
    $newJob = AnalysisJob::orderBy('id', 'desc')->first();
    expect($newJob->id)->not->toBe($failed->id);
    expect($newJob->status)->toBe('pending');
    expect(AuditLog::where('action', 'job_retry')->exists())->toBeTrue();
    Queue::assertPushed(ProcessAnalysisJob::class);
    // Cannot retry pending (policy 403 or validation)
    $pending = createJobForTest('pending');
    $response = $this->actingAs($admin)->post(route('analysis-jobs.retry', $pending));
    expect($response->status())->toBeIn([302, 403]);
    if ($response->status() === 302) {
        $response->assertSessionHasErrors('job');
    }
});

test('cancel updates status and audits', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $processing = createJobForTest('processing');
    $this->actingAs($admin)->post(route('analysis-jobs.cancel', $processing))->assertRedirect();
    expect(AnalysisJob::find($processing->id)->status)->toBe('cancelled');
    expect(AuditLog::where('action', 'job_cancelled')->exists())->toBeTrue();
    // Cannot cancel completed (policy 403)
    $completed = createJobForTest('completed');
    $response = $this->actingAs($admin)->post(route('analysis-jobs.cancel', $completed));
    expect($response->status())->toBeIn([302, 403]);
    if ($response->status() === 302) {
        $response->assertSessionHasErrors('job');
    }
});

test('edit updates pending job and audits', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $job = createJobForTest('pending');
    $newSession = ExamSession::create(['name' => 'NewSession', 'status' => 'pending', 'created_by' => $admin->id]);
    $this->actingAs($admin)->get(route('analysis-jobs.edit', $job))->assertStatus(200);
    $this->actingAs($admin)->put(route('analysis-jobs.update', $job), [
        'exam_session_id' => $newSession->id,
        'source_type' => 'test_source',
        'model_version_id' => $job->model_version_id,
        'video_asset_id' => $job->video_asset_id,
    ])->assertRedirect();
    expect(AnalysisJob::find($job->id)->exam_session_id)->toBe($newSession->id);
    expect(AuditLog::where('action', 'job_edited')->exists())->toBeTrue();
    // Cannot edit non-pending
    $failed = createJobForTest('failed');
    $this->actingAs($admin)->get(route('analysis-jobs.edit', $failed))->assertStatus(403);
});

test('authorization enforced', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))->first();
    $reviewer = User::whereHas('roles', fn ($q) => $q->where('name', 'reviewer'))->first();
    $pending = createJobForTest('pending');
    $failed = createJobForTest('failed');
    // Auditor cannot delete
    $this->actingAs($auditor)->delete(route('analysis-jobs.destroy', $pending))->assertStatus(403);
    // Auditor cannot retry
    $this->actingAs($auditor)->post(route('analysis-jobs.retry', $failed))->assertStatus(403);
    // Reviewer cannot edit
    $this->actingAs($reviewer)->get(route('analysis-jobs.edit', $pending))->assertStatus(403);
    // Auditor cannot cancel
    $processing = createJobForTest('processing');
    $this->actingAs($auditor)->post(route('analysis-jobs.cancel', $processing))->assertStatus(403);
    // Admin can delete
    $this->actingAs($admin)->delete(route('analysis-jobs.destroy', $pending))->assertRedirect();
});

test('status based actions visibility', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $pending = createJobForTest('pending');
    $response = $this->actingAs($admin)->get(route('analysis-jobs.index'));
    $response->assertSee('View');
    $response->assertSee('Edit');
    // Create other statuses and check
    $queued = createJobForTest('queued');
    $this->actingAs($admin)->get(route('analysis-jobs.index'))->assertSee('Cancel');
    $failed = createJobForTest('failed');
    $this->actingAs($admin)->get(route('analysis-jobs.index'))->assertSee('Retry');
    $completed = createJobForTest('completed');
    $this->actingAs($admin)->get(route('analysis-jobs.index'))->assertSee('Report');
});
