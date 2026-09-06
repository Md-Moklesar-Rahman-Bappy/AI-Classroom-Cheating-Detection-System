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

test('create user with role dropdown and audit', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->get(route('users.create'))->assertStatus(200)->assertSee('System Administrator')->assertSee('Full system control')->assertSee('Confirm Password');
    $reviewerRole = Role::where('name', 'reviewer')->first();
    $resp = $this->actingAs($admin)->post(route('users.store'), ['name' => 'New Reviewer', 'email' => 'newrev@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'roles' => [$reviewerRole->id]]);
    $resp->assertRedirect(route('users.index'));
    $u = User::where('email', 'newrev@example.com')->first();
    expect($u)->not->toBeNull();
    expect($u->hasRole('reviewer'))->toBeTrue();
    expect(\App\Models\AuditLog::where('action', 'user_created')->where('target_id', (string) $u->id)->exists())->toBeTrue();
});

test('edit role with confirmation and audit logs old/new', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $user = User::factory()->create();
    $user->roles()->sync([Role::where('name', 'reviewer')->first()->id]);
    $this->actingAs($admin)->get(route('users.edit', $user))->assertStatus(200)->assertSee('Current role')->assertSee('Reviewer');
    $auditorRole = Role::where('name', 'auditor')->first();
    $resp = $this->actingAs($admin)->put(route('users.update', $user), ['name' => $user->name, 'email' => $user->email, 'roles' => [$auditorRole->id]]);
    $resp->assertRedirect(route('users.index'));
    expect($user->fresh()->hasRole('auditor'))->toBeTrue();
    $log = \App\Models\AuditLog::where('action', 'role_updated')->where('target_id', (string) $user->id)->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->metadata['old_roles'])->toContain('reviewer');
    expect($log->metadata['new_roles'])->toContain('auditor');
});

test('validate role required and exists', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->post(route('users.store'), ['name' => 'NoRole', 'email' => 'norole@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'roles' => []])->assertSessionHasErrors('roles');
    $this->actingAs($admin)->post(route('users.store'), ['name' => 'BadRole', 'email' => 'badrole@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'roles' => [9999]])->assertSessionHasErrors('roles.0');
});

test('unauthorized role assignment blocked', function () {
    $invigilator = User::whereHas('roles', fn ($q) => $q->where('name', 'invigilator'))->first();
    $this->actingAs($invigilator)->get(route('users.create'))->assertStatus(403);
    $this->actingAs($invigilator)->post(route('users.store'), ['name' => 'Hack', 'email' => 'hack@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'roles' => [Role::where('name', 'auditor')->first()->id]])->assertStatus(403);
    $reviewer = User::whereHas('roles', fn ($q) => $q->where('name', 'reviewer'))->first();
    $this->actingAs($reviewer)->get(route('users.index'))->assertStatus(403);
});

test('system_admin self removal and last admin protection', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $auditorRole = Role::where('name', 'auditor')->first();
    $resp = $this->actingAs($admin)->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'roles' => [$auditorRole->id]]);
    // Create second admin to allow demotion
    $secondAdmin = User::factory()->create();
    $secondAdmin->roles()->sync([Role::where('name', 'system_admin')->first()->id]);
    $resp2 = $this->actingAs($admin)->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'roles' => [$auditorRole->id]]);
    $resp2->assertRedirect();
    expect($admin->fresh()->hasRole('system_admin'))->toBeFalse();
    // Last admin deletion blocked
    User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->where('id', '!=', $secondAdmin->id)->delete();
    $this->actingAs($secondAdmin)->delete(route('users.destroy', $secondAdmin))->assertSessionHasErrors('user');
});

test('role filter search and badge rendering', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $this->actingAs($admin)->get(route('users.index', ['role' => 'reviewer']))->assertStatus(200)->assertSee('Reviewer');
    $this->actingAs($admin)->get(route('users.index', ['search' => 'auditor@example.com']))->assertStatus(200)->assertSee('auditor@example.com');
    $this->actingAs($admin)->get(route('users.index'))->assertStatus(200)->assertSee('System Administrator')->assertSee('bg-danger')->assertSee('bg-success')->assertSee('bg-primary');
});
