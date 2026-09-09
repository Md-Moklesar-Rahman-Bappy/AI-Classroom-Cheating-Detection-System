<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detection_events', function (Blueprint $table) {
            if (!Schema::hasColumn('detection_events', 'trigger_frame_number')) {
                $table->integer('trigger_frame_number')->nullable()->after('ended_at_frame');
            }
            if (!Schema::hasColumn('detection_events', 'trigger_timestamp')) {
                $table->double('trigger_timestamp')->nullable()->after('trigger_frame_number');
            }
            if (!Schema::hasColumn('detection_events', 'last_detection_frame_number')) {
                $table->integer('last_detection_frame_number')->nullable()->after('trigger_timestamp');
            }
            if (!Schema::hasColumn('detection_events', 'last_detection_timestamp')) {
                $table->double('last_detection_timestamp')->nullable()->after('last_detection_frame_number');
            }
            if (!Schema::hasColumn('detection_events', 'last_detection_bbox_json')) {
                $table->json('last_detection_bbox_json')->nullable()->after('last_detection_timestamp');
            }
            if (!Schema::hasColumn('detection_events', 'absence_processed_frames')) {
                $table->integer('absence_processed_frames')->nullable()->after('last_detection_bbox_json');
            }
            if (!Schema::hasColumn('detection_events', 'absence_source_frames_json')) {
                $table->json('absence_source_frames_json')->nullable()->after('absence_processed_frames');
            }
            if (!Schema::hasColumn('detection_events', 'bbox_format')) {
                $table->string('bbox_format', 10)->default('xyxy')->after('absence_source_frames_json');
            }
            if (!Schema::hasColumn('detection_events', 'processed_frame_width')) {
                $table->integer('processed_frame_width')->default(640)->after('bbox_format');
            }
            if (!Schema::hasColumn('detection_events', 'processed_frame_height')) {
                $table->integer('processed_frame_height')->default(360)->after('processed_frame_width');
            }
            if (!Schema::hasColumn('detection_events', 'source_frame_width')) {
                $table->integer('source_frame_width')->default(64)->after('processed_frame_height');
            }
            if (!Schema::hasColumn('detection_events', 'source_frame_height')) {
                $table->integer('source_frame_height')->default(48)->after('source_frame_width');
            }
            if (!Schema::hasColumn('detection_events', 'temporal_status')) {
                $table->string('temporal_status', 30)->default('ok')->after('source_frame_height');
            }
        });

        Schema::table('event_evidence', function (Blueprint $table) {
            if (!Schema::hasColumn('event_evidence', 'render_mode')) {
                $table->string('render_mode', 20)->default('trigger')->after('file_type');
            }
            if (!Schema::hasColumn('event_evidence', 'trigger_frame_number')) {
                $table->integer('trigger_frame_number')->nullable()->after('render_mode');
            }
            if (!Schema::hasColumn('event_evidence', 'last_detection_frame_number')) {
                $table->integer('last_detection_frame_number')->nullable()->after('trigger_frame_number');
            }
            if (!Schema::hasColumn('event_evidence', 'last_detection_bbox_json')) {
                $table->json('last_detection_bbox_json')->nullable()->after('last_detection_frame_number');
            }
            if (!Schema::hasColumn('event_evidence', 'two_frame_evidence_json')) {
                $table->json('two_frame_evidence_json')->nullable()->after('last_detection_bbox_json');
            }
            if (!Schema::hasColumn('event_evidence', 'presence_valid_detection_frame')) {
                $table->integer('presence_valid_detection_frame')->nullable()->after('two_frame_evidence_json');
            }
            if (!Schema::hasColumn('event_evidence', 'absence_source_frames_json')) {
                $table->json('absence_source_frames_json')->nullable()->after('presence_valid_detection_frame');
            }
        });
    }

    public function down(): void
    {
    }
};
