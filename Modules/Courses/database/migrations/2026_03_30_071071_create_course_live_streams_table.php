<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create((config('courses.db_prefix') ?? 'courses_') . 'course_live_streams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('meeting_link')->nullable();
            $table->dateTime('date_time');
            $table->integer('duration_minutes')->nullable();
            $table->unsignedTinyInteger('status')->default(1); // 1: Scheduled, 2: Completed, 3: Cancelled
            $table->integer('notify_hours_before')->default(24);
            $table->timestamps();
            
            // Assuming course table has id
            $table->foreign('course_id')
                  ->references('id')
                  ->on((config('courses.db_prefix') ?? 'courses_') . 'courses')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists((config('courses.db_prefix') ?? 'courses_') . 'course_live_streams');
    }
};
