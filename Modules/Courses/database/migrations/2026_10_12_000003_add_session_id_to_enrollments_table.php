<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The date a student chose when buying an in-person course (null for online courses). */
return new class extends Migration {
    public function up(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'enrollments';
        if (!Schema::hasColumn($table, 'session_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('session_id')->nullable()->after('course_id')->index();
            });
        }
    }

    public function down(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'enrollments';
        if (Schema::hasColumn($table, 'session_id')) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('session_id'));
        }
    }
};
