<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_rooms', 'deleted_at')) $table->softDeletes();
        });
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_sessions', 'deleted_at')) $table->softDeletes();
        });
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'deleted_at')) $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('exam_rooms', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
        });
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
        });
    }
};
