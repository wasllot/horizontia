<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** When the 24 h reminder of an in-person date was e-mailed to this student (null = not yet), so it is never sent twice. */
return new class extends Migration {
    public function up(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'enrollments';
        if (!Schema::hasColumn($table, 'reminded_at')) {
            Schema::table($table, fn (Blueprint $t) => $t->timestamp('reminded_at')->nullable()->after('session_id'));
        }
    }

    public function down(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'enrollments';
        if (Schema::hasColumn($table, 'reminded_at')) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('reminded_at'));
        }
    }
};
