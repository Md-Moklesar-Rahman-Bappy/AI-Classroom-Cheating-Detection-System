<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewDecision extends Model
{
    public $timestamps = false;

    protected $fillable = ['detection_event_id', 'exam_session_id', 'reviewed_by', 'decision', 'note', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
