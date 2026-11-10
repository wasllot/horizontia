<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Paquetes del flyer de Astrofest Gemínidas 2026 (precio con IVA incluido). */
    private const ASTROFEST_PACKAGES = [
        ['key' => 'p1', 'name' => '1 persona', 'persons' => 1, 'price' => 1300, 'active' => true],
        ['key' => 'p2', 'name' => '2 personas', 'persons' => 2, 'price' => 2100, 'active' => true],
        ['key' => 'p3', 'name' => '3 personas', 'persons' => 3, 'price' => 2900, 'active' => true],
    ];

    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'packages')) {
                $table->json('packages')->nullable()->after('price');                      // paquetes por número de personas; vacío = entrada individual al precio base
            }
            if (!Schema::hasColumn('events', 'hotel_email')) {
                $table->string('hotel_email', 255)->nullable()->after('hotel_whatsapp');   // recibe el código de cada compra (varios, separados por coma)
            }
            if (!Schema::hasColumn('events', 'hotel_summary_sent_at')) {
                $table->dateTime('hotel_summary_sent_at')->nullable();
            }
        });

        Schema::table('event_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('event_orders', 'items')) {
                $table->json('items')->nullable()->after('quantity');                      // paquetes comprados (copia del nombre, personas y precio de ese momento)
            }
            if (!Schema::hasColumn('event_orders', 'hotel_code')) {
                $table->string('hotel_code', 24)->nullable()->unique()->after('payment_method');
            }
            if (!Schema::hasColumn('event_orders', 'hotel_notified_at')) {
                $table->dateTime('hotel_notified_at')->nullable()->after('confirmation_sent_at');
            }
        });

        // Astrofest estrena los paquetes del flyer; solo si todavía no se configuraron (no pisa lo que se edite en el panel).
        DB::table('events')->where('slug', 'astrofest-geminidas-2026')->whereNull('packages')
            ->update(['packages' => json_encode(self::ASTROFEST_PACKAGES)]);

        // Hasta 10 personas por compra (p. ej. 2 paquetes de 3 + 1 de 2 + 1 de 1 cabe en una sola compra).
        DB::table('events')->where('slug', 'astrofest-geminidas-2026')->where('max_per_order', '<', 10)->update(['max_per_order' => 10]);
    }

    public function down(): void
    {
        Schema::table('event_orders', function (Blueprint $table) {
            foreach (['hotel_code', 'hotel_notified_at', 'items'] as $column) {
                if (Schema::hasColumn('event_orders', $column)) {
                    if ($column === 'hotel_code') {
                        $table->dropUnique(['hotel_code']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
        Schema::table('events', function (Blueprint $table) {
            foreach (['hotel_summary_sent_at', 'hotel_email', 'packages'] as $column) {
                if (Schema::hasColumn('events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
