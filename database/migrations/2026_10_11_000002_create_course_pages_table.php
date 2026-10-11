<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->index();
            $table->unsignedBigInteger('page_id')->index();
            $table->string('label', 120);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_pages');
    }
};
