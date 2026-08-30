<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventEvidence extends Model
{
    protected $table = 'event_evidence';

    protected $fillable = ['detection_event_id', 'file_path', 'file_type', 'frame_number', 'captured_at_seconds', 'width', 'height', 'checksum_sha256'];

    public function event()
    {
        return $this->belongsTo(DetectionEvent::class, 'detection_event_id');
    }
}
