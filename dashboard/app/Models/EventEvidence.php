<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventEvidence extends Model
{
    use SoftDeletes;

    protected $table = 'event_evidence';

    protected $fillable = ['detection_event_id', 'file_path', 'file_type', 'frame_number', 'captured_at_seconds', 'width', 'height', 'checksum_sha256', 'bbox_json', 'event_type', 'debug_json', 'archived_at'];

    protected $casts = ['bbox_json' => 'array', 'debug_json' => 'array'];

    public function event()
    {
        return $this->belongsTo(DetectionEvent::class, 'detection_event_id');
    }
}
