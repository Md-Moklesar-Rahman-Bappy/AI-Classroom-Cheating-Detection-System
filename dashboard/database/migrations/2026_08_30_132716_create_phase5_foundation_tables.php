<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('group', 100);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('exam_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('building', 150)->nullable();
            $table->integer('capacity')->nullable();
            $table->text('camera_position_notes')->nullable();
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_room_id')->nullable()->constrained('exam_rooms')->nullOnDelete();
            $table->string('name', 200);
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['exam_room_id', 'status']);
        });

        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('version', 50);
            $table->string('weight_filename', 255);
            $table->string('checksum_sha256', 64)->unique();
            $table->json('class_list');
            $table->string('training_dataset_version', 100)->nullable();
            $table->integer('image_size')->nullable();
            $table->string('license', 50);
            $table->string('source_url', 500)->nullable();
            $table->json('framework_versions')->nullable();
            $table->timestamps();
            $table->unique(['name', 'version']);
        });

        Schema::create('camera_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->nullable()->constrained('exam_sessions')->nullOnDelete();
            $table->string('name', 200);
            $table->enum('source_type', ['webcam', 'rtsp', 'http', 'video_file', 'test_source']);
            $table->string('identifier', 500);
            $table->text('credentials_encrypted')->nullable();
            $table->enum('status', ['inactive', 'testing', 'connected', 'failed'])->default('inactive');
            $table->dateTime('last_tested_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['exam_session_id', 'source_type']);
        });

        Schema::create('video_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255)->unique();
            $table->string('mime_type', 100);
            $table->bigInteger('size_bytes');
            $table->float('duration_seconds')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->float('fps')->nullable();
            $table->string('codec', 50)->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->enum('validation_status', ['pending', 'valid', 'invalid'])->default('pending');
            $table->text('validation_error')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['exam_session_id', 'validation_status']);
        });

        Schema::create('analysis_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->enum('source_type', ['recorded_video', 'live_stream', 'webcam', 'test_source']);
            $table->foreignId('video_asset_id')->nullable()->constrained('video_assets')->nullOnDelete();
            $table->foreignId('camera_source_id')->nullable()->constrained('camera_sources')->nullOnDelete();
            $table->foreignId('model_version_id')->constrained('model_versions')->cascadeOnDelete();
            $table->enum('status', ['pending', 'queued', 'processing', 'paused', 'cancelled', 'failed', 'completed'])->default('pending');
            $table->json('config');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['exam_session_id', 'status']);
        });

        Schema::create('detection_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('analysis_job_id')->constrained('analysis_jobs')->cascadeOnDelete();
            $table->foreignId('model_version_id')->constrained('model_versions')->cascadeOnDelete();
            $table->enum('source_type', ['recorded_video', 'live_stream', 'webcam', 'test_source']);
            $table->integer('temporary_track_id');
            $table->enum('event_type', ['D1', 'D2', 'B1', 'B2', 'B3', 'B4']);
            $table->enum('event_status', ['active', 'ended'])->default('active');
            $table->integer('started_at_frame')->nullable();
            $table->integer('ended_at_frame')->nullable();
            $table->float('started_at_seconds')->nullable();
            $table->float('ended_at_seconds')->nullable();
            $table->float('confidence')->nullable();
            $table->float('rule_score')->nullable();
            $table->boolean('evidence_available')->default(false);
            $table->enum('review_status', ['pending', 'confirmed_suspicious', 'dismissed_normal', 'needs_further_review'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('reviewer_note', 500)->nullable();
            $table->timestamps();
            $table->index(['exam_session_id', 'event_type']);
            $table->index(['analysis_job_id', 'review_status']);
        });

        Schema::create('event_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detection_event_id')->constrained('detection_events')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->enum('file_type', ['snapshot', 'clip']);
            $table->integer('frame_number')->nullable();
            $table->float('captured_at_seconds')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('review_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detection_event_id')->constrained('detection_events')->cascadeOnDelete();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->cascadeOnDelete();
            $table->enum('decision', ['confirmed_suspicious', 'dismissed_normal', 'needs_further_review']);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('processing_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_job_id')->unique()->constrained('analysis_jobs')->cascadeOnDelete();
            $table->float('source_fps')->nullable();
            $table->float('processing_fps')->nullable();
            $table->float('detection_latency_ms')->nullable();
            $table->float('end_to_end_alert_latency_ms')->nullable();
            $table->float('cpu_percent')->nullable();
            $table->float('memory_mb')->nullable();
            $table->float('gpu_percent')->nullable();
            $table->integer('dropped_frames')->default(0);
            $table->integer('queue_size')->nullable();
            $table->integer('reconnect_count')->default(0);
            $table->float('job_duration_seconds')->nullable();
            $table->float('video_duration_to_processing_ratio')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('target_type', 100)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->json('metadata')->nullable();
            $table->enum('result', ['success', 'failure']);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['actor_id', 'action']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('retention_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['scheduled', 'executed', 'failed']);
            $table->string('target_type', 100);
            $table->string('target_id', 100);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_actions');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('processing_metrics');
        Schema::dropIfExists('review_decisions');
        Schema::dropIfExists('event_evidence');
        Schema::dropIfExists('detection_events');
        Schema::dropIfExists('analysis_jobs');
        Schema::dropIfExists('video_assets');
        Schema::dropIfExists('camera_sources');
        Schema::dropIfExists('model_versions');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('exam_rooms');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
