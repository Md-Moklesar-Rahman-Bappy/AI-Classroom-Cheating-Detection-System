<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE detection_events MODIFY COLUMN event_type ENUM('D1','D2','D3','B1','B2','B3','B4','B5','S1','S2','S3') NOT NULL");
        } elseif ($driver === 'sqlite') {
            try {
                DB::statement("ALTER TABLE detection_events RENAME TO detection_events_old");
                DB::statement("CREATE TABLE detection_events (id INTEGER PRIMARY KEY AUTOINCREMENT, exam_session_id INTEGER NOT NULL, analysis_job_id INTEGER NOT NULL, model_version_id INTEGER NOT NULL, source_type VARCHAR(255) NOT NULL, temporary_track_id INTEGER NOT NULL, event_type VARCHAR(10) NOT NULL CHECK(event_type IN ('D1','D2','D3','B1','B2','B3','B4','B5','S1','S2','S3')), event_status VARCHAR(255) NOT NULL DEFAULT 'active', started_at_frame INTEGER, ended_at_frame INTEGER, started_at_seconds FLOAT, ended_at_seconds FLOAT, confidence FLOAT, rule_score FLOAT, evidence_available BOOLEAN DEFAULT 0, review_status VARCHAR(255) NOT NULL DEFAULT 'pending', reviewed_by INTEGER, reviewed_at DATETIME, reviewer_note VARCHAR(500), created_at DATETIME, updated_at DATETIME, FOREIGN KEY(exam_session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE, FOREIGN KEY(analysis_job_id) REFERENCES analysis_jobs(id) ON DELETE CASCADE, FOREIGN KEY(model_version_id) REFERENCES model_versions(id) ON DELETE CASCADE)");
                DB::statement("INSERT INTO detection_events SELECT * FROM detection_events_old");
                DB::statement("DROP TABLE detection_events_old");
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE detection_events MODIFY COLUMN event_type ENUM('D1','D2','B1','B2','B3','B4') NOT NULL");
        }
    }
};
