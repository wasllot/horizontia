<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        Schema::create($prefix . 'assignment_submissions', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained($prefix . 'curriculums')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->text('student_comment')->nullable();
            $table->integer('score')->nullable(); // 0 to 100
            $table->text('tutor_feedback')->nullable();
            $table->enum('status', ['submitted', 'graded'])->default('submitted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('courses.db_prefix') ?? 'courses_';
        Schema::dropIfExists($prefix . 'assignment_submissions');
    }
};
