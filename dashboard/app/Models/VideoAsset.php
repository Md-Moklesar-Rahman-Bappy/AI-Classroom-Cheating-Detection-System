<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoAsset extends Model
{
    use SoftDeletes;

    protected $fillable = ['exam_session_id', 'original_filename', 'stored_filename', 'mime_type', 'size_bytes', 'duration_seconds', 'width', 'height', 'fps', 'codec', 'checksum_sha256', 'validation_status', 'validation_error', 'uploaded_by'];

    protected $dates = ['deleted_at'];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function analysisJobs()
    {
        return $this->hasMany(AnalysisJob::class, 'video_asset_id');
    }

    public function linkedJobCount(): Attribute
    {
        return new Attribute(
            get: fn () => $this->analysisJobs()->count(),
            set: fn ($value) => null,
        );
    }
}
