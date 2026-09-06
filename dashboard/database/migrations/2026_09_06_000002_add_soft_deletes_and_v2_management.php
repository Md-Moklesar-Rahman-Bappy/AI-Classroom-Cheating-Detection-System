<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camera_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('camera_sources', 'deleted_at')) {
                $table->softDeletes();
            }
        });
        Schema::table('detection_events', function (Blueprint $table) {
            if (! Schema::hasColumn('detection_events', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('detection_events', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('reviewed_at');
            }
        });
        Schema::table('event_evidence', function (Blueprint $table) {
            if (! Schema::hasColumn('event_evidence', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('event_evidence', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('checksum_sha256');
            }
            if (! Schema::hasColumn('event_evidence', 'file_checksum')) {
                $table->string('file_checksum', 64)->nullable()->after('checksum_sha256');
            }
        });
        try {
            DB::statement("UPDATE detection_events SET event_type='D1' WHERE event_type='Person Detected'");
            DB::statement("UPDATE detection_events SET event_type='D2' WHERE event_type='Mobile Phone Detected'");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::table('camera_sources', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
        });
        Schema::table('detection_events', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
            if (Schema::hasColumn('detection_events', 'archived_at')) $table->dropColumn('archived_at');
        });
        Schema::table('event_evidence', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
            if (Schema::hasColumn('event_evidence', 'archived_at')) $table->dropColumn('archived_at');
        });
    }
};
