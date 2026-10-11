<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_entries', function (Blueprint $table) {
            $table->id();
            // path: exact URL path ("cursos", "/" for home) | route: route name | pattern: wildcard path ("curso/*")
            $table->string('match_type', 12)->default('path');
            $table->string('match_value', 191);
            $table->string('label')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('keywords')->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('canonical', 500)->nullable();
            $table->string('robots', 40)->nullable();
            $table->longText('schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['match_type', 'match_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_entries');
    }
};
