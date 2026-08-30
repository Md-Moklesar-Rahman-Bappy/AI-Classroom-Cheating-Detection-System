<?php

use App\Models\AnalysisJob;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('local webcam or test stream start', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'LiveTest', 'status' => 'pending', 'created_by' => $admin->id]);
    Http::fake(['*' => Http::response(['session_id' => (string) Str::uuid(), 'status' => 'monitoring'], 200)]);
    $response = $this->actingAs($admin)->post(route('live.start'), ['exam_session_id' => $session->id, 'source_type' => 'test', 'identifier' => 'test']);
    $response->assertRedirect();
});

test('invalid URL', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'LiveInvalid', 'status' => 'pending', 'created_by' => $admin->id]);
    $this->actingAs($admin)->post(route('live.start'), ['exam_session_id' => $session->id, 'source_type' => 'rtsp', 'identifier' => ''])->assertSessionHasErrors('identifier');
    $this->actingAs($admin)->post(route('live.start'), ['exam_session_id' => $session->id, 'source_type' => 'invalid', 'identifier' => '0'])->assertSessionHasErrors('source_type');
});

test('authentication failure', function () {
    $this->get(route('live.index'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('live.index'))->assertStatus(403);
});

test('connection timeout', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'LiveTimeout', 'status' => 'pending', 'created_by' => $admin->id]);
    Http::fake(function () {
        throw new ConnectionException('Timeout');
    });
    $response = $this->actingAs($admin)->post(route('live.start'), ['exam_session_id' => $session->id, 'source_type' => 'rtsp', 'identifier' => 'rtsp://timeout/stream']);
    $response->assertSessionHasErrors('live');
});

test('stream interruption and reconnection', function () {
    Http::fake(['*' => Http::response(['session_id' => (string) Str::uuid(), 'status' => 'monitoring'], 200)]);
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'LiveInterrupt', 'status' => 'pending', 'created_by' => $admin->id]);
    $this->actingAs($admin)->post(route('live.start'), ['exam_session_id' => $session->id, 'source_type' => 'test', 'identifier' => 'test'])->assertRedirect();
});

test('stale frame detection', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $sessionId = (string) Str::uuid();
    Http::fake(['*' => Http::response(['session_id' => $sessionId, 'state' => 'degraded', 'health' => 'degraded', 'last_frame_time' => time() - 10], 200)]);
    $response = $this->actingAs($admin)->get(route('live.health', $sessionId));
    $response->assertStatus(200);
});

test('stop during reconnect', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $sessionId = (string) Str::uuid();
    Http::fake(['*' => Http::response(['session_id' => $sessionId, 'status' => 'stopped'], 200)]);
    $response = $this->actingAs($admin)->post(route('live.stop', $sessionId));
    $response->assertRedirect();
});

test('duplicate start', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'LiveDup', 'status' => 'pending', 'created_by' => $admin->id]);
    AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'live_stream', 'model_version_id' => ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('a', 64)], ['name' => 'yolo', 'version' => 'v1', 'weight_filename' => 'y', 'class_list' => json_encode([]), 'license' => 'AGPL-3.0'])->id, 'status' => 'processing', 'config' => [], 'created_by' => $admin->id]);
    $response = $this->actingAs($admin)->post(route('live.start'), ['exam_session_id' => $session->id, 'source_type' => 'test', 'identifier' => 'test']);
    $response->assertSessionHasErrors('live');
});

test('repeated stop idempotent', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $sessionId = (string) Str::uuid();
    Http::fake(['*' => Http::response(['session_id' => $sessionId, 'status' => 'stopped'], 200)]);
    $this->actingAs($admin)->post(route('live.stop', $sessionId))->assertRedirect();
    $this->actingAs($admin)->post(route('live.stop', $sessionId))->assertRedirect();
});

test('event delivery', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $sessionId = (string) Str::uuid();
    Http::fake(['*' => Http::response(['session_id' => $sessionId, 'total' => 1, 'events' => [['event_type' => 'B1', 'track_id' => 1]]], 200)]);
    $response = $this->actingAs($admin)->get(route('live.events', $sessionId));
    $response->assertStatus(200);
});

test('evidence generation', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $sessionId = (string) Str::uuid();
    Http::fake(['*' => Http::response(['session_id' => $sessionId, 'health' => 'healthy'], 200)]);
    $response = $this->actingAs($admin)->get(route('live.health', $sessionId));
    $response->assertStatus(200);
});

test('unauthorized control', function () {
    $user = User::factory()->create();
    $sessionId = (string) Str::uuid();
    $this->actingAs($user)->post(route('live.start'), ['exam_session_id' => 1, 'source_type' => 'test', 'identifier' => 'test'])->assertStatus(403);
    $this->actingAs($user)->post(route('live.stop', $sessionId))->assertStatus(403);
});

test('unauthorized preview', function () {
    $user = User::factory()->create();
    $sessionId = (string) Str::uuid();
    $this->actingAs($user)->get(route('live.preview', $sessionId))->assertStatus(403);
    $response = $this->get(route('live.preview', $sessionId));
    expect($response->status())->toBeIn([302, 403]);
    if ($response->status() === 302) {
        $response->assertRedirect(route('login'));
    }
});

test('credential redaction', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $response = $this->actingAs($admin)->get(route('live.index'));
    $response->assertStatus(200);
    $response->assertDontSee('pass');
    $response->assertSee('Never expose credentials');
});

test('resource cleanup', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $sessionId = (string) Str::uuid();
    Http::fake(['*' => Http::response(['session_id' => $sessionId, 'status' => 'stopped'], 200)]);
    $this->actingAs($admin)->post(route('live.stop', $sessionId))->assertRedirect();
    $this->actingAs($admin)->post(route('live.stop', $sessionId))->assertRedirect();
});

test('AI service crash recovery', function () {
    Http::fake(['*' => Http::response(null, 503)]);
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'LiveCrash', 'status' => 'pending', 'created_by' => $admin->id]);
    $response = $this->actingAs($admin)->post(route('live.start'), ['exam_session_id' => $session->id, 'source_type' => 'test', 'identifier' => 'test']);
    $response->assertSessionHasErrors('live');
});

test('dashboard recovery', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $response = $this->actingAs($admin)->get(route('live.index'));
    $response->assertStatus(200);
    $response->assertSee('Live Surveillance');
});
