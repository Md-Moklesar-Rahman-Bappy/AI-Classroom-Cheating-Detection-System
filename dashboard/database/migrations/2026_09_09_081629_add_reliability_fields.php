<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detection_events', function (Blueprint $table) {
            if (!Schema::hasColumn('detection_events', 'trigger_sha256')) {
                $table->string('trigger_sha256', 64)->nullable()->after('trigger_frame_number');
            }
            if (!Schema::hasColumn('detection_events', 'last_detected_sha256')) {
                $table->string('last_detected_sha256', 64)->nullable()->after('last_detection_frame_number');
            }
            if (!Schema::hasColumn('detection_events', 'evidence_integrity_status')) {
                $table->string('evidence_integrity_status', 20)->default('not_verified')->after('temporal_status');
            }
            if (!Schema::hasColumn('detection_events', 'evidence_completeness_status')) {
                $table->string('evidence_completeness_status', 20)->default('complete')->after('evidence_integrity_status');
            }
            if (!Schema::hasColumn('detection_events', 'missing_frame_reason')) {
                $table->string('missing_frame_reason', 30)->nullable()->after('evidence_completeness_status');
            }
            if (!Schema::hasColumn('detection_events', 'suppressed_duplicate_count')) {
                $table->integer('suppressed_duplicate_count')->default(0)->after('missing_frame_reason');
            }
            if (!Schema::hasColumn('detection_events', 'track_generation')) {
                $table->integer('track_generation')->default(1)->after('suppressed_duplicate_count');
            }
            if (!Schema::hasColumn('detection_events', 'association_confidence')) {
                $table->float('association_confidence')->nullable()->after('track_generation');
            }
            if (!Schema::hasColumn('detection_events', 'reassociation_reason')) {
                $table->string('reassociation_reason', 50)->nullable()->after('association_confidence');
            }
        });
        Schema::table('event_evidence', function (Blueprint $table) {
            if (!Schema::hasColumn('event_evidence', 'integrity_status')) {
                $table->string('integrity_status', 20)->default('not_verified')->after('checksum_sha256');
            }
            if (!Schema::hasColumn('event_evidence', 'completeness_status')) {
                $table->string('completeness_status', 20)->default('complete')->after('integrity_status');
            }
            if (!Schema::hasColumn('event_evidence', 'missing_reason')) {
                $table->string('missing_reason', 30)->nullable()->after('completeness_status');
            }
        });
        Schema::table('processing_metrics', function (Blueprint $table) {
            if (!Schema::hasColumn('processing_metrics', 'suppressed_duplicate_count')) {
                $table->integer('suppressed_duplicate_count')->default(0)->after('dropped_frames');
            }
            if (!Schema::hasColumn('processing_metrics', 'evidence_save_success')) {
                $table->integer('evidence_save_success')->default(0)->after('suppressed_duplicate_count');
            }
            if (!Schema::hasColumn('processing_metrics', 'evidence_save_failure')) {
                $table->integer('evidence_save_failure')->default(0)->after('evidence_save_success');
            }
            if (!Schema::hasColumn('processing_metrics', 'incomplete_evidence_count')) {
                $table->integer('incomplete_evidence_count')->default(0)->after('evidence_save_failure');
            }
            if (!Schema::hasColumn('processing_metrics', 'hash_verification_failures')) {
                $table->integer('hash_verification_failures')->default(0)->after('incomplete_evidence_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detection_events', function (Blueprint $table) {
            foreach (['trigger_sha256','last_detected_sha256','evidence_integrity_status','evidence_completeness_status','missing_frame_reason','suppressed_duplicate_count','track_generation','association_confidence','reassociation_reason'] as $col) {
                if (Schema::hasColumn('detection_events', $col)) $table->dropColumn($col);
            }
        });
        Schema::table('event_evidence', function (Blueprint $table) {
            foreach (['integrity_status','completeness_status','missing_reason'] as $col) {
                if (Schema::hasColumn('event_evidence', $col)) $table->dropColumn($col);
            }
        });
        Schema::table('processing_metrics', function (Blueprint $table) {
            foreach (['suppressed_duplicate_count','evidence_save_success','evidence_save_failure','incomplete_evidence_count','hash_verification_failures'] as $col) {
                if (Schema::hasColumn('processing_metrics', $col)) $table->dropColumn($col);
            }
        });
    }
};
