<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'target_type', 'target_id', 'ip_address', 'user_agent', 'correlation_id', 'metadata', 'result', 'created_at'];

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];
}
