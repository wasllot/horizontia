<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);                          // student | tutor
            $table->string('name', 120);
            $table->string('email', 150)->index();
            $table->string('phone', 30)->nullable();
            $table->text('message')->nullable();                 // alumno: qué quiere aprender · instructor: experiencia
            $table->string('link', 250)->nullable();             // instructor: sitio, LinkedIn o portafolio
            $table->string('status', 10)->default('pending')->index();   // pending | approved | rejected
            $table->unsignedBigInteger('user_id')->nullable();   // cuenta creada al aprobar
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_note', 300)->nullable();      // motivo si se rechaza (se envía a la persona)
            $table->dateTime('activated_at')->nullable();        // cuándo usó el enlace de activación
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
