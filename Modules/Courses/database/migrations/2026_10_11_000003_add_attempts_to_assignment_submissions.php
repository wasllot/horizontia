<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Quiz attempts: how many times a student sent the test and when they passed it. */
return new class extends Migration {
    public function up(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'assignment_submissions';
        if (!Schema::hasColumn($table, 'attempts')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedSmallInteger('attempts')->default(0);
                $t->timestamp('passed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'assignment_submissions';
        if (Schema::hasColumn($table, 'attempts')) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['attempts', 'passed_at']));
        }
    }
};
