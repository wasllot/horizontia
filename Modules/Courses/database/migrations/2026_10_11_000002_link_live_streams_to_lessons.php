<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A "live" lesson owns a scheduled session: the stream points back at its lesson (null for stand-alone sessions). */
return new class extends Migration {
    public function up(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams';
        if (!Schema::hasColumn($table, 'curriculum_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('curriculum_id')->nullable()->after('course_id')->index();
                $t->string('provider', 30)->nullable()->after('meeting_link');
                $t->string('recording_url')->nullable()->after('provider');
            });
        }
    }

    public function down(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams';
        if (Schema::hasColumn($table, 'curriculum_id')) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['curriculum_id', 'provider', 'recording_url']));
        }
    }
};
