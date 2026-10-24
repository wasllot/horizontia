<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('reason', 500);
            $table->string('status', 10)->default('pending')->index();   // pending | approved | rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_note', 300)->nullable();             // motivo si se rechaza (se envía a la persona)
            $table->unsignedTinyInteger('progress')->default(0);        // avance más alto de sus cursos al pedirlo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
