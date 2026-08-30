<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelVersion extends Model
{
    protected $fillable = ['name', 'version', 'weight_filename', 'checksum_sha256', 'class_list', 'training_dataset_version', 'image_size', 'license', 'source_url', 'framework_versions', 'is_active'];

    protected $casts = ['class_list' => 'array', 'framework_versions' => 'array', 'is_active' => 'boolean', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
