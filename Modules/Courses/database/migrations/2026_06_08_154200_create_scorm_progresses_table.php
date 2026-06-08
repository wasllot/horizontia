<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';

        Schema::create($prefix . 'scorm_progresses', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('curriculum_id');
            $table->foreign('curriculum_id')->references('id')->on($prefix . 'curriculums')->cascadeOnDelete();
            
            $table->string('lesson_status')->default('incomplete');
            $table->integer('score_raw')->nullable();
            $table->string('session_time')->nullable();
            $table->text('suspend_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        Schema::dropIfExists($prefix . 'scorm_progresses');
    }
};
