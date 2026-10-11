<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per date of an in-person course: when, where and (optionally) how many seats. Times are stored in UTC. */
return new class extends Migration {
    public function up(): void
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        if (Schema::hasTable($prefix . 'course_sessions')) {
            return;
        }
        Schema::create($prefix . 'course_sessions', function (Blueprint $t) use ($prefix) {
            $t->id();
            $t->unsignedBigInteger('course_id')->index();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at')->nullable();
            $t->string('timezone', 64)->default('America/Mexico_City');
            $t->string('schedule_note', 160)->nullable();
            $t->string('venue_name', 160);
            $t->string('address', 255);
            $t->string('city', 120)->index();
            $t->string('state', 120)->nullable();
            $t->string('maps_url', 500)->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->text('venue_notes')->nullable();
            $t->unsignedInteger('capacity')->nullable();          // null = unlimited
            $t->dateTime('sales_close_at')->nullable();           // null = closes when it starts
            $t->string('status', 12)->default('scheduled');       // scheduled | cancelled
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->index(['course_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((config('courses.db_prefix') ?? 'courses_') . 'course_sessions');
    }
};
