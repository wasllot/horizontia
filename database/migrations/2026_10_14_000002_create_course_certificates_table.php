<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('course_id')->index();
            // Copia de lo que dice el certificado el día que se emitió: aunque cambie el nombre del curso, el documento no cambia.
            $table->string('student_name');
            $table->string('course_title');
            $table->string('instructor_name')->nullable();
            $table->unsignedInteger('content_seconds')->default(0);
            $table->dateTime('issued_at');
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoked_reason', 160)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_certificates');
    }
};
