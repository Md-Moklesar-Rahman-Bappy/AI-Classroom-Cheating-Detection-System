<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessingMetric extends Model
{
    protected $fillable = ['analysis_job_id', 'source_fps', 'processing_fps', 'detection_latency_ms', 'end_to_end_alert_latency_ms', 'cpu_percent', 'memory_mb', 'gpu_percent', 'dropped_frames', 'queue_size', 'reconnect_count', 'job_duration_seconds', 'video_duration_to_processing_ratio','suppressed_duplicate_count','evidence_save_success','evidence_save_failure','incomplete_evidence_count','hash_verification_failures'];

    public function analysisJob(): BelongsTo
    {
        return $this->belongsTo(AnalysisJob::class);
    }
}
