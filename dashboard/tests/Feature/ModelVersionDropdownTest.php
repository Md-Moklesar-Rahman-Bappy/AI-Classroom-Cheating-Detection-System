<?php

use App\Models\AnalysisJob;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\User;
use Database\Seeders\ModelVersionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ModelVersionSeeder::class);
});

test('dropdown populated', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $response = $this->actingAs($admin)->get(route('analysis-jobs.create'));
    $response->assertStatus(200);
    $response->assertSee('YOLO11 Nano');
    $response->assertSee('1.0');
});

test('active model available', function () {
    expect(ModelVersion::active()->count())->toBeGreaterThan(0);
    $model = ModelVersion::active()->first();
    expect($model->is_active)->toBeTrue();
    expect($model->name)->toBe('YOLO11 Nano');
    expect($model->checksum_sha256)->toBe('0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1');
});

test('job creation succeeds with active model', function () {
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $session = ExamSession::create(['name' => 'TestSession', 'status' => 'pending', 'created_by' => $admin->id]);
    $model = ModelVersion::active()->first();
    $response = $this->actingAs($admin)->post(route('analysis-jobs.store'), [
        'exam_session_id' => $session->id,
        'source_type' => 'test_source',
        'model_version_id' => $model->id,
    ]);
    $response->assertRedirect();
    expect(AnalysisJob::where('model_version_id', $model->id)->exists())->toBeTrue();
});

test('fresh install contains at least one active model', function () {
    // Simulate fresh install by checking seeder
    $this->assertDatabaseHas('model_versions', ['name' => 'YOLO11 Nano', 'is_active' => true]);
});

test('inactive model not in dropdown', function () {
    $inactive = ModelVersion::create([
        'name' => 'Old Model',
        'version' => '0.1',
        'weight_filename' => 'old.pt',
        'checksum_sha256' => str_repeat('f', 64),
        'class_list' => json_encode(['person']),
        'license' => 'AGPL-3.0',
        'is_active' => false,
    ]);
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->first();
    $response = $this->actingAs($admin)->get(route('analysis-jobs.create'));
    $response->assertDontSee('Old Model');
    $response->assertSee('YOLO11 Nano');
});
