<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventEvidence extends Model
{
    use SoftDeletes;

    protected $table = 'event_evidence';

    protected $fillable = ['detection_event_id', 'file_path', 'file_type', 'frame_number', 'captured_at_seconds', 'width', 'height', 'checksum_sha256', 'bbox_json', 'event_type', 'debug_json', 'archived_at','render_mode','trigger_frame_number','last_detection_frame_number','last_detection_bbox_json','two_frame_evidence_json','presence_valid_detection_frame','absence_source_frames_json','integrity_status','completeness_status','missing_reason'];

    protected $casts = ['bbox_json' => 'array', 'debug_json' => 'array'];

    public function event()
    {
        return $this->belongsTo(DetectionEvent::class, 'detection_event_id');
    }
}
