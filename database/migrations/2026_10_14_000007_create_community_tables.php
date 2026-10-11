<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('curriculum_id')->nullable();           // lección a la que se refiere la pregunta
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable()->index();      // null = pregunta; con valor = respuesta
            $table->string('title', 140)->nullable();
            $table->text('body');
            $table->boolean('pinned')->default(false);
            $table->boolean('is_answer')->default(false);                      // respuesta marcada como solución
            $table->dateTime('hidden_at')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);
            $table->dateTime('last_activity_at')->nullable();
            $table->timestamps();
            $table->index(['course_id', 'parent_id', 'pinned', 'last_activity_at'], 'cp_course_list_idx');
        });

        Schema::create('community_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reports');
        Schema::dropIfExists('community_likes');
        Schema::dropIfExists('community_posts');
    }
};
