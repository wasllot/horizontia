<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_low');            // siempre el id menor: así cada pareja tiene una sola conversación
            $table->unsignedBigInteger('user_high');
            $table->dateTime('last_message_at')->nullable()->index();
            $table->string('last_preview', 140)->nullable();
            $table->unsignedBigInteger('last_sender_id')->nullable();
            $table->timestamps();
            $table->unique(['user_low', 'user_high']);
            $table->index('user_high');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->text('body')->nullable();
            $table->string('file_path', 300)->nullable();      // en el disco privado; se sirve con permiso
            $table->string('file_name', 160)->nullable();
            $table->string('file_mime', 80)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'sender_id', 'read_at'], 'chat_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
