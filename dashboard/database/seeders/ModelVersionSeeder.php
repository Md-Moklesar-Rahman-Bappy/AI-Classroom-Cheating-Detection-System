<?php

namespace Database\Seeders;

use App\Models\ModelVersion;
use Illuminate\Database\Seeder;

class ModelVersionSeeder extends Seeder
{
    public function run(): void
    {
        ModelVersion::firstOrCreate(
            ['name' => 'YOLO11 Nano', 'version' => '1.0'],
            [
                'weight_filename' => 'yolo11n.pt',
                'checksum_sha256' => '0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1',
                'class_list' => json_encode(['person', 'cell phone']),
                'training_dataset_version' => 'COCO 2017',
                'image_size' => 640,
                'license' => 'AGPL-3.0',
                'source_url' => 'https://github.com/ultralytics/ultralytics',
                'framework_versions' => json_encode(['ultralytics' => '8.4.135', 'torch' => '2.13.0']),
                'is_active' => true,
            ]
        );
    }
}
