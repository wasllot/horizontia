<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id')->index();
            $table->unsignedBigInteger('curriculum_id');
            $table->string('kind', 10)->default('note');             // note | bookmark
            $table->text('body')->nullable();
            $table->unsignedInteger('seconds')->nullable();          // minuto del video al que se refiere
            $table->timestamps();
            $table->index(['user_id', 'curriculum_id']);
        });

        Schema::create('lesson_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('curriculum_id');
            $table->unsignedInteger('seconds')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'curriculum_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_positions');
        Schema::dropIfExists('lesson_notes');
    }
};
