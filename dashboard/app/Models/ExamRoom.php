<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamRoom extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'building', 'capacity', 'camera_position_notes'];

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}
