<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventEvidence extends Model
{
    use SoftDeletes;

    protected $table = 'event_evidence';

    protected $fillable = ['detection_event_id', 'file_path', 'file_type', 'frame_number', 'captured_at_seconds', 'width', 'height', 'checksum_sha256', 'archived_at'];

    public function event()
    {
        return $this->belongsTo(DetectionEvent::class, 'detection_event_id');
    }
}
