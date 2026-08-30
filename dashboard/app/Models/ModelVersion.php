<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'version', 'weight_filename', 'checksum_sha256', 'class_list', 'training_dataset_version', 'image_size', 'license', 'source_url', 'framework_versions'];

    protected $casts = ['class_list' => 'array', 'framework_versions' => 'array', 'created_at' => 'datetime'];
}
