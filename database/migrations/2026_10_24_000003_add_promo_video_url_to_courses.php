<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function table(): string
    {
        return (config('courses.db_prefix') ?? 'courses_') . 'courses';
    }

    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->string('promo_video_url', 300)->nullable();   // YouTube o Vimeo; sustituye al video subido si existe
        });
    }

    public function down(): void
    {
        Schema::table($this->table(), fn (Blueprint $table) => $table->dropColumn('promo_video_url'));
    }
};
