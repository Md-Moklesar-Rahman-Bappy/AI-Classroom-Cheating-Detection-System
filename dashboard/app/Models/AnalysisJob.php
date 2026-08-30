<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisJob extends Model
{
    protected $fillable = ['exam_session_id', 'source_type', 'video_asset_id', 'camera_source_id', 'model_version_id', 'status', 'remote_job_id', 'remote_status', 'remote_progress', 'remote_output_metadata', 'correlation_id', 'config', 'progress_percent', 'started_at', 'completed_at', 'failed_at', 'failure_reason', 'created_by'];

    protected $casts = ['config' => 'array', 'remote_output_metadata' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function modelVersion()
    {
        return $this->belongsTo(ModelVersion::class, 'model_version_id');
    }

    public function videoAsset()
    {
        return $this->belongsTo(VideoAsset::class, 'video_asset_id');
    }

    public function events()
    {
        return $this->hasMany(DetectionEvent::class, 'analysis_job_id');
    }

    public function metrics()
    {
        return $this->hasOne(ProcessingMetric::class, 'analysis_job_id');
    }
}
