<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $fillable = ['exam_room_id', 'name', 'status', 'started_at', 'ended_at', 'created_by'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    public function room()
    {
        return $this->belongsTo(ExamRoom::class, 'exam_room_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function videoAssets()
    {
        return $this->hasMany(VideoAsset::class, 'exam_session_id');
    }

    public function analysisJobs()
    {
        return $this->hasMany(AnalysisJob::class, 'exam_session_id');
    }

    public function detectionEvents()
    {
        return $this->hasMany(DetectionEvent::class, 'exam_session_id');
    }
}
