<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 190)->unique();
            $table->string('subtitle', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 10)->default('draft');           // draft | active
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('learning_path_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained('learning_paths')->cascadeOnDelete();
            $table->unsignedBigInteger('course_id')->index();
            $table->unsignedInteger('sort')->default(0);
            $table->unique(['learning_path_id', 'course_id']);
        });

        // El certificado también puede ser de una ruta completa (entonces no hay curso).
        Schema::table('course_certificates', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->change();
            $table->unsignedBigInteger('learning_path_id')->nullable()->after('course_id')->index();
            $table->unique(['user_id', 'learning_path_id']);
        });
    }

    public function down(): void
    {
        Schema::table('course_certificates', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'learning_path_id']);
            $table->dropColumn('learning_path_id');
        });
        Schema::dropIfExists('learning_path_courses');
        Schema::dropIfExists('learning_paths');
    }
};
