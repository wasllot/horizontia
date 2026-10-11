<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_banners', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 60)->unique();          // page:{id} para páginas del editor, system:{nombre} para páginas fijas (p. ej. contacto)
            $table->boolean('enabled')->default(true);
            $table->string('title', 140)->nullable();
            $table->string('subtitle', 280)->nullable();
            $table->string('image', 500)->nullable();          // ruta en el disco de archivos
            $table->unsignedTinyInteger('overlay')->default(55); // oscurecimiento de la imagen, 0–85 %
            $table->string('align', 10)->default('left');       // left | center
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_banners');
    }
};
