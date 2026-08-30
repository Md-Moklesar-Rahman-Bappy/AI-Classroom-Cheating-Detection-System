<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_jobs', function (Blueprint $table) {
            $table->string('remote_job_id', 36)->nullable()->after('id')->index();
            $table->string('remote_status', 20)->nullable()->after('status');
            $table->unsignedTinyInteger('remote_progress')->nullable()->after('progress_percent');
            $table->json('remote_output_metadata')->nullable()->after('config');
            $table->string('correlation_id', 36)->nullable()->after('remote_job_id');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_jobs', function (Blueprint $table) {
            $table->dropColumn(['remote_job_id', 'remote_status', 'remote_progress', 'remote_output_metadata', 'correlation_id']);
        });
    }
};
