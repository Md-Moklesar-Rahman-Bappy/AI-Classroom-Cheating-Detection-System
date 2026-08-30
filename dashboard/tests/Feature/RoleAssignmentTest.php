<?php

use App\Models\AnalysisJob;
use App\Models\DetectionEvent;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('user role assignment', function () {
    $user = User::factory()->create();
    $role = Role::where('name', 'system_admin')->first();
    $user->roles()->attach($role);
    expect($user->fresh()->roles->pluck('name'))->toContain('system_admin');
    expect($user->fresh()->hasRole('system_admin'))->toBeTrue();
});

test('role sync replaces roles', function () {
    $user = User::factory()->create();
    $admin = Role::where('name', 'system_admin')->first();
    $auditor = Role::where('name', 'auditor')->first();
    $user->roles()->sync([$admin->id]);
    expect($user->fresh()->roles->count())->toBe(1);
    $user->roles()->sync([$auditor->id]);
    expect($user->fresh()->roles->pluck('name'))->toContain('auditor');
    expect($user->fresh()->roles->pluck('name'))->not->toContain('system_admin');
});

test('sidebar role display shows description and fallback', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('System Administrator');
    $response->assertDontSee('No Role Assigned');

    $noRoleUser = User::factory()->create();
    $response2 = $this->actingAs($noRoleUser)->get(route('dashboard'));
    $response2->assertStatus(200);
    $response2->assertSee('No Role Assigned');
});

test('system admin access', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->get(route('users.index'))->assertStatus(200);
    $this->actingAs($admin)->get(route('exam-rooms.index'))->assertStatus(200);
    $this->actingAs($admin)->get(route('audit-logs.index'))->assertStatus(200);
});

test('auditor denial', function () {
    $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))->first();
    $this->actingAs($auditor)->get(route('users.index'))->assertStatus(403);
    $this->actingAs($auditor)->get(route('audit-logs.index'))->assertStatus(200);
    $this->actingAs($auditor)->get(route('exam-rooms.index'))->assertStatus(200);
});

test('reviewer access rules', function () {
    $reviewer = User::whereHas('roles', fn ($q) => $q->where('name', 'reviewer'))->first();
    $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))->first();
    $invigilator = User::whereHas('roles', fn ($q) => $q->where('name', 'invigilator'))->first();
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'ReviewTest', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::firstOrCreate(['checksum_sha256' => str_repeat('z', 64)], ['name' => 'yolo', 'version' => 'v1', 'weight_filename' => 'y', 'class_list' => json_encode([]), 'license' => 'AGPL-3.0']);
    $job = AnalysisJob::create(['exam_session_id' => $session->id, 'source_type' => 'test_source', 'model_version_id' => $model->id, 'status' => 'completed', 'config' => [], 'created_by' => $admin->id]);
    $event = DetectionEvent::create(['exam_session_id' => $session->id, 'analysis_job_id' => $job->id, 'model_version_id' => $model->id, 'source_type' => 'test_source', 'temporary_track_id' => 1, 'event_type' => 'B1', 'review_status' => 'pending']);

    $this->actingAs($reviewer)->post(route('detection-events.review', $event), ['decision' => 'confirmed_suspicious', 'note' => 'ok'])->assertRedirect();
    $this->actingAs($auditor)->post(route('detection-events.review', $event), ['decision' => 'dismissed_normal'])->assertStatus(403);
    $this->actingAs($invigilator)->post(route('detection-events.review', $event), ['decision' => 'dismissed_normal'])->assertStatus(403);
});
