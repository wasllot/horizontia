<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function table(): string
    {
        return (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams';
    }

    public function up(): void
    {
        if (!Schema::hasColumn($this->table(), 'reminded_at')) {
            Schema::table($this->table(), function (Blueprint $table) {
                $table->dateTime('reminded_at')->nullable();   // cuándo salió el recordatorio (una sola vez, sin depender de la caché)
            });
        }
    }

    public function down(): void
    {
        Schema::table($this->table(), fn (Blueprint $table) => $table->dropColumn('reminded_at'));
    }
};
