<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bundles ("paquetes"): several courses sold together at one price. */
return new class extends Migration {
    public function up(): void
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        if (!Schema::hasTable($prefix . 'bundles')) {
            Schema::create($prefix . 'bundles', function (Blueprint $t) {
                $t->id();
                $t->string('title', 160);
                $t->string('slug', 190)->unique();
                $t->string('subtitle', 255)->nullable();
                $t->text('description')->nullable();
                $t->decimal('price', 10, 2);
                $t->string('status', 12)->default('draft');   // draft | active
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });
        }
        if (!Schema::hasTable($prefix . 'bundle_items')) {
            Schema::create($prefix . 'bundle_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('bundle_id')->index();
                $t->unsignedBigInteger('course_id')->index();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->unique(['bundle_id', 'course_id']);
            });
        }
    }

    public function down(): void
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        Schema::dropIfExists($prefix . 'bundle_items');
        Schema::dropIfExists($prefix . 'bundles');
    }
};
