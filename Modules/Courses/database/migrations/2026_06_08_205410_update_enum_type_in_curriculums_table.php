<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        DB::statement("ALTER TABLE {$prefix}curriculums MODIFY COLUMN type ENUM('video', 'audio', 'live', 'article', 'yt_link', 'vm_link', 'genially_link', 'scorm', 'assignment') DEFAULT 'video'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        DB::statement("ALTER TABLE {$prefix}curriculums MODIFY COLUMN type ENUM('video', 'audio', 'live', 'article', 'yt_link', 'vm_link', 'genially_link', 'scorm') DEFAULT 'video'");
    }
};
