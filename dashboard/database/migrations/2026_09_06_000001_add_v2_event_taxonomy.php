<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE detection_events MODIFY COLUMN event_type ENUM('D1','D2','D3','B1','B2','B3','B4','B5','S1','S2','S3') NOT NULL");
        } elseif ($driver === 'sqlite') {
            return;
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE detection_events MODIFY COLUMN event_type ENUM('D1','D2','B1','B2','B3','B4') NOT NULL");
        }
    }
};
