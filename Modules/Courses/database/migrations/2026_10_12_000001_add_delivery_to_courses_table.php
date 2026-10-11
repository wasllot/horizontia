<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Course modality: online (default, nothing changes), in_person or hybrid (in-person dates + optional online lessons). */
return new class extends Migration {
    public function up(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'courses';
        if (!Schema::hasColumn($table, 'delivery')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('delivery', 12)->default('online')->after('type')->index();
            });
        }
    }

    public function down(): void
    {
        $table = (config('courses.db_prefix') ?? 'courses_') . 'courses';
        if (Schema::hasColumn($table, 'delivery')) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('delivery'));
        }
    }
};
