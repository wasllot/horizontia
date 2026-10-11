<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_phone', 20)->nullable();      // solo dígitos, con código de país
            $table->dateTime('whatsapp_optin_at')->nullable();     // cuándo aceptó recibir avisos por WhatsApp
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('event', 20);                            // purchase | live | onsite | test
            $table->string('subject', 40);                          // p. ej. order:12 — evita enviar dos veces lo mismo
            $table->string('template', 80);
            $table->string('to_tail', 6)->nullable();               // últimos dígitos, para el registro sin guardar el número completo
            $table->string('status', 10)->default('queued');        // queued | sent | failed | skipped
            $table->string('wamid', 120)->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'event', 'subject']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['whatsapp_phone', 'whatsapp_optin_at']));
    }
};
