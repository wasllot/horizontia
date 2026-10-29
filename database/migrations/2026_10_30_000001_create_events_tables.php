<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Evento de entrada única (p. ej. Astrofest): página propia, carrito de invitado y pago con Mercado Pago.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 160);
            $table->string('status', 12)->default('draft')->index();         // draft | published | finished
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->dateTime('countdown_from')->nullable();                  // el contador se muestra desde esta fecha
            $table->string('venue_name', 160)->nullable();
            $table->string('venue_city', 160)->nullable();
            $table->string('venue_address', 300)->nullable();
            $table->string('venue_map_url', 500)->nullable();                // vacío = el mapa se oculta
            $table->string('hotel_booking_url', 500)->nullable();            // vacío = el botón de habitación se oculta
            $table->string('hotel_phone', 40)->nullable();
            $table->string('hotel_whatsapp', 40)->nullable();
            $table->string('contact_email', 160)->nullable();
            $table->string('contact_whatsapp', 40)->nullable();
            $table->decimal('price', 10, 2)->default(0);                     // precio final con IVA incluido
            $table->decimal('iva_rate', 5, 2)->default(16);
            $table->unsignedInteger('capacity')->nullable();                 // vacío = sin cupo (nunca "Agotado")
            $table->unsignedTinyInteger('max_per_order')->default(10);
            $table->boolean('sales_enabled')->default(false);                // interruptor maestro de ventas
            $table->dateTime('sales_open_at')->nullable();
            $table->dateTime('sales_close_at')->nullable();
            $table->unsignedSmallInteger('hold_minutes')->default(45);       // cuánto se guarda un lugar mientras se paga
            $table->unsignedSmallInteger('offline_hold_hours')->default(72); // efectivo / transferencia
            $table->json('discounts')->nullable();                           // preventa y grupo (apagados por defecto)
            $table->json('content')->nullable();                             // textos y bloques editables / ocultables
            $table->json('assets')->nullable();                              // rutas de logos e imágenes subidas
            $table->longText('policies_html')->nullable();                   // sin políticas no se abren ventas
            $table->boolean('redirect_events_index')->default(false);        // /eventos → este evento (302)
            $table->timestamps();
        });

        Schema::create('event_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('code', 26)->unique();                            // identificador público (no adivinable)
            $table->string('name', 160);
            $table->string('email', 190)->index();
            $table->string('phone', 40);
            $table->unsignedTinyInteger('quantity');
            $table->unsignedTinyInteger('minors')->default(0);               // informativo: no cambia el precio
            $table->boolean('has_telescope')->nullable();
            $table->string('heard_from', 120)->nullable();
            $table->boolean('newsletter')->default(false);
            $table->boolean('wants_invoice')->default(false);
            $table->json('invoice')->nullable();
            $table->dateTime('policies_accepted_at')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('discount_label', 160)->nullable();
            $table->decimal('total', 10, 2);
            $table->decimal('iva', 10, 2)->default(0);                       // IVA contenido en el total
            $table->string('status', 12)->default('pending');               // pending | paid | expired | refunded
            $table->dateTime('expires_at')->nullable();                      // fin de la reserva del lugar
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_id', 64)->nullable()->index();
            $table->string('payment_method', 40)->nullable();
            $table->json('utm')->nullable();
            $table->dateTime('confirmation_sent_at')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'status']);
        });

        Schema::create('event_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_order_id')->constrained('event_orders')->cascadeOnDelete();
            $table->string('attendee_name', 160);
            $table->string('code', 20)->unique();                            // clave de acceso / contenido del QR
            $table->dateTime('checked_in_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
        Schema::dropIfExists('event_orders');
        Schema::dropIfExists('events');
    }
};
