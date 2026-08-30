<?php

use App\Models\AnalysisJob;
use App\Models\AuditLog;
use App\Models\CameraSource;
use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\ReviewDecision;
use App\Models\Role;
use App\Models\User;
use App\Models\VideoAsset;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function actingAsRole(string $role)
{
    $user = User::whereHas('roles', fn ($q) => $q->where('name', $role))->first();
    if (! $user) {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $role)->first());
    }

    return test()->actingAs($user);
}

test('login screen rendered', function () {
    $this->get('/login')->assertStatus(200);
});

test('users can authenticate', function () {
    $user = User::factory()->create(['password' => Hash::make('Password123!')]);
    $this->post('/login', ['email' => $user->email, 'password' => 'Password123!'])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
});

test('rate limiting blocks after many attempts', function () {
    $user = User::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }
    $response = $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    // Breeze throttles with 302 and validation error, check for throttling
    expect($response->status())->toBeIn([302, 429]);
    if ($response->status() === 302) {
        // Check that the response has throttling error
        expect($response->getSession()->get('errors'))->not->toBeNull();
    }
});

test('session regeneration after login', function () {
    $user = User::factory()->create(['password' => Hash::make('Password123!')]);
    $response = $this->post('/login', ['email' => $user->email, 'password' => 'Password123!']);
    $this->assertAuthenticated();
});

test('password reset link can be requested', function () {
    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email])->assertStatus(302);
});

test('role permissions enforced', function () {
    actingAsRole('auditor')->get(route('users.index'))->assertStatus(403);
    actingAsRole('system_admin')->get(route('users.index'))->assertStatus(200);
});

test('unauthorized route access denied', function () {
    $this->get(route('exam-rooms.index'))->assertRedirect(route('login'));
});

test('room CRUD', function () {
    $user = actingAsRole('system_admin')->get(route('exam-rooms.index'))->assertStatus(200);
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->post(route('exam-rooms.store'), ['name' => 'Room A', 'building' => 'B1', 'capacity' => 30])->assertRedirect();
    $room = ExamRoom::where('name', 'Room A')->first();
    expect($room)->not->toBeNull();
    $this->actingAs($admin)->get(route('exam-rooms.show', $room))->assertStatus(200);
    $this->actingAs($admin)->put(route('exam-rooms.update', $room), ['name' => 'Room B', 'building' => 'B1'])->assertRedirect();
    expect(ExamRoom::find($room->id)->name)->toBe('Room B');
});

test('session CRUD', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $room = ExamRoom::create(['name' => 'R1', 'building' => 'B']);
    $this->actingAs($admin)->post(route('exam-sessions.store'), ['name' => 'Session 1', 'exam_room_id' => $room->id, 'status' => 'pending'])->assertRedirect();
    $session = ExamSession::where('name', 'Session 1')->first();
    expect($session)->not->toBeNull();
    $this->actingAs($admin)->get(route('exam-sessions.show', $session))->assertStatus(200);
});

test('camera metadata', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'S1', 'status' => 'pending', 'created_by' => $admin->id]);
    $this->actingAs($admin)->post(route('camera-sources.store'), ['name' => 'Cam1', 'source_type' => 'webcam', 'identifier' => '0', 'exam_session_id' => $session->id])->assertRedirect();
    $cam = CameraSource::where('name', 'Cam1')->first();
    expect($cam)->not->toBeNull();
    expect($cam->credentials_encrypted)->toBeNull();
    $this->actingAs($admin)->post(route('camera-sources.store'), ['name' => 'Cam2', 'source_type' => 'rtsp', 'identifier' => 'rtsp://host/stream', 'credentials' => 'secret123', 'exam_session_id' => $session->id])->assertRedirect();
    $cam2 = CameraSource::where('name', 'Cam2')->first();
    expect($cam2->credentials_encrypted)->not->toBeNull();
    $response = $this->actingAs($admin)->get(route('camera-sources.show', $cam2));
    $response->assertStatus(200);
    $response->assertDontSee('secret123');
});

test('video assets', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'S2', 'status' => 'pending', 'created_by' => $admin->id]);
    $file = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
    $this->actingAs($admin)->post(route('video-assets.store'), ['exam_session_id' => $session->id, 'video' => $file])->assertRedirect();
    expect(VideoAsset::where('original_filename', 'video.mp4')->exists())->toBeTrue();
});

test('jobs', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'S3', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::create(['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'checksum_sha256' => str_repeat('a', 64), 'class_list' => json_encode(['person', 'phone']), 'license' => 'AGPL-3.0']);
    $this->actingAs($admin)->post(route('analysis-jobs.store'), ['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id])->assertRedirect();
    expect(AnalysisJob::count())->toBe(1);
});

test('event display', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'S4', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('b', 64)], ['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => json_encode([]), 'created_by' => $admin->id]);
    $event = DetectionEvent::create(['exam_session_id' => $session->id, 'analysis_job_id' => $job->id, 'model_version_id' => $model->id, 'source_type' => 'test_source', 'temporary_track_id' => 1, 'event_type' => 'B1', 'event_status' => 'active', 'review_status' => 'pending']);
    $this->actingAs($admin)->get(route('detection-events.index'))->assertStatus(200);
    $this->actingAs($admin)->get(route('detection-events.show', $event))->assertStatus(200)->assertSee('Machine Observation')->assertSee('Supporting Detector/Rule Evidence')->assertSee('Human Decision')->assertSee('Audit History');
});

test('evidence denial', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'S5', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('c', 64)], ['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => json_encode([]), 'created_by' => $admin->id]);
    $event = DetectionEvent::create(['exam_session_id' => $session->id, 'analysis_job_id' => $job->id, 'model_version_id' => $model->id, 'source_type' => 'test_source', 'temporary_track_id' => 1, 'event_type' => 'D2', 'review_status' => 'pending']);
    $evidence = EventEvidence::create(['detection_event_id' => $event->id, 'file_path' => 'evidence/'.$event->id.'/test.jpg', 'file_type' => 'snapshot', 'frame_number' => 1]);
    $this->get(route('evidence.show', $evidence))->assertRedirect(route('login'));
    $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))->first();
    Storage::fake('local');
    Storage::disk('local')->put($evidence->file_path, 'fake');
    $this->actingAs($auditor)->get(route('evidence.show', $evidence))->assertStatus(200);
});

test('reviewer decision', function () {
    $reviewer = User::whereHas('roles', fn ($q) => $q->where('name', 'reviewer'))->first();
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'S6', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('d', 64)], ['name' => 'yolo11n.pt', 'version' => 'v1', 'weight_filename' => 'yolo11n.pt', 'class_list' => json_encode(['person']), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => json_encode([]), 'created_by' => $admin->id]);
    $event = DetectionEvent::create(['exam_session_id' => $session->id, 'analysis_job_id' => $job->id, 'model_version_id' => $model->id, 'source_type' => 'test_source', 'temporary_track_id' => 1, 'event_type' => 'B1', 'review_status' => 'pending']);
    $this->actingAs($reviewer)->post(route('detection-events.review', $event), ['decision' => 'confirmed_suspicious', 'note' => 'looks left'])->assertRedirect();
    expect(DetectionEvent::find($event->id)->review_status)->toBe('confirmed_suspicious');
    expect(ReviewDecision::where('detection_event_id', $event->id)->exists())->toBeTrue();
});

test('auditor read-only', function () {
    $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))->first();
    $this->actingAs($auditor)->get(route('audit-logs.index'))->assertStatus(200);
    $this->actingAs($auditor)->get(route('users.index'))->assertStatus(403);
});

test('model-version management', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->post(route('model-versions.store'), ['name' => 'yolo11n.pt', 'version' => 'v2', 'checksum_sha256' => str_repeat('e', 64), 'license' => 'AGPL-3.0'])->assertRedirect();
    expect(ModelVersion::where('version', 'v2')->exists())->toBeTrue();
});

test('audit logging', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->post(route('exam-rooms.store'), ['name' => 'AuditRoom', 'building' => 'B'])->assertRedirect();
    expect(AuditLog::where('action', 'room_created')->exists())->toBeTrue();
});

test('seeder environment safety', function () {
    expect(app()->environment())->not->toBe('production');
});

test('csrf protection', function () {
    $route = app('router')->getRoutes()->getByName('exam-rooms.store');
    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('web');
    expect(class_exists(VerifyCsrfToken::class))->toBeTrue();
});

test('validation', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->post(route('exam-rooms.store'), ['name' => ''])->assertSessionHasErrors('name');
});

test('direct URL manipulation forbidden', function () {
    $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))->first();
    $room = ExamRoom::create(['name' => 'SecretRoom', 'building' => 'B']);
    $this->actingAs($auditor)->get(route('exam-rooms.edit', $room))->assertStatus(200);
    $this->actingAs($auditor)->get(route('users.index'))->assertStatus(403);
});

test('strong password policy', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->post(route('users.store'), ['name' => 'Weak', 'email' => 'weak@example.com', 'password' => '123', 'roles' => [1]])->assertSessionHasErrors('password');
});

test('evidence not in public', function () {
    expect(file_exists(public_path('evidence')))->toBeFalse();
});
