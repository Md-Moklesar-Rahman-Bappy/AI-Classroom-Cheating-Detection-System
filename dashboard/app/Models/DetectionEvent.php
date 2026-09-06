<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetectionEvent extends Model
{
    protected $fillable = ['exam_session_id', 'analysis_job_id', 'model_version_id', 'source_type', 'temporary_track_id', 'event_type', 'event_status', 'started_at_frame', 'ended_at_frame', 'started_at_seconds', 'ended_at_seconds', 'confidence', 'rule_score', 'evidence_available', 'review_status', 'reviewed_by', 'reviewed_at', 'reviewer_note'];

    public const EVENT_CODES = ['D1','D2','D3','B1','B2','B3','B4','B5','S1','S2','S3'];
    public const CATEGORY_MAP = ['D1'=>'detection','D2'=>'detection','D3'=>'detection','B1'=>'behavior','B2'=>'behavior','B3'=>'behavior','B4'=>'behavior','B5'=>'behavior','S1'=>'system','S2'=>'system','S3'=>'system'];

    public function getEventCategoryAttribute(): string
    {
        return self::CATEGORY_MAP[$this->event_type] ?? 'unknown';
    }

    protected $casts = ['evidence_available' => 'boolean', 'reviewed_at' => 'datetime'];

    public function job()
    {
        return $this->belongsTo(AnalysisJob::class, 'analysis_job_id');
    }

    public function analysisJob()
    {
        return $this->belongsTo(AnalysisJob::class, 'analysis_job_id');
    }

    public function evidences()
    {
        return $this->hasMany(EventEvidence::class, 'detection_event_id');
    }

    public function evidence()
    {
        return $this->hasMany(EventEvidence::class, 'detection_event_id');
    }

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}
