<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoAsset extends Model
{
    protected $fillable = ['exam_session_id', 'original_filename', 'stored_filename', 'mime_type', 'size_bytes', 'duration_seconds', 'width', 'height', 'fps', 'codec', 'checksum_sha256', 'validation_status', 'validation_error', 'uploaded_by'];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
