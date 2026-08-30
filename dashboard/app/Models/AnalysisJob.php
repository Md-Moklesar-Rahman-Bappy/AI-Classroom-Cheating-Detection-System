<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisJob extends Model
{
    protected $fillable = ['exam_session_id', 'source_type', 'video_asset_id', 'camera_source_id', 'model_version_id', 'status', 'config', 'progress_percent', 'started_at', 'completed_at', 'failed_at', 'failure_reason', 'created_by'];

    protected $casts = ['config' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}
