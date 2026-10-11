<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Sections become reorderable: the outline is ordered by sort_order, then id (existing rows keep their creation order). */
return new class extends Migration {
    public function up(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'sections';
        if (!Schema::hasColumn($table, 'sort_order')) {
            Schema::table($table, fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0)->after('description'));
            // Seed the order from the current ids, per course.
            foreach (DB::table($table)->orderBy('course_id')->orderBy('id')->get(['id', 'course_id']) as $row) {
                static $counters = [];
                $counters[$row->course_id] = ($counters[$row->course_id] ?? -1) + 1;
                DB::table($table)->where('id', $row->id)->update(['sort_order' => $counters[$row->course_id]]);
            }
        }
    }

    public function down(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'sections';
        if (Schema::hasColumn($table, 'sort_order')) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('sort_order'));
        }
    }
};
