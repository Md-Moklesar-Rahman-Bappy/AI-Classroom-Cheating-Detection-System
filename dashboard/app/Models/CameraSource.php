<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CameraSource extends Model
{
    use SoftDeletes;

    protected $fillable = ['exam_session_id', 'name', 'source_type', 'identifier', 'credentials_encrypted', 'status', 'last_tested_at', 'created_by', 'archived_at'];

    protected $hidden = ['credentials_encrypted'];

    protected $casts = ['last_tested_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function getHasCredentialsAttribute()
    {
        return ! empty($this->credentials_encrypted);
    }
}
