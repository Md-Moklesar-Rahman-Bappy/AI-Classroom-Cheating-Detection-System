<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamRoom extends Model
{
    protected $fillable = ['name', 'building', 'capacity', 'camera_position_notes'];

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}
