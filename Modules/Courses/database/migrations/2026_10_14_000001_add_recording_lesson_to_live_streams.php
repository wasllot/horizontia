<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La grabación de una sesión en vivo se publica como lección del curso: aquí se recuerda cuál es (para no duplicarla). */
return new class extends Migration {
    public function up(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams';
        if (!Schema::hasColumn($table, 'recording_curriculum_id')) {
            Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('recording_curriculum_id')->nullable()->after('recording_url')->index());
        }
    }

    public function down(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams';
        if (Schema::hasColumn($table, 'recording_curriculum_id')) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('recording_curriculum_id'));
        }
    }
};
