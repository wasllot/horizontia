<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Crea el evento Astrofest Gemínidas 2026 con lo que ya está confirmado. Lo pendiente (políticas, cupo, mapa, hotel, contacto)
 * queda vacío y por tanto oculto, y las ventas apagadas hasta que se capture en Admin → Eventos. Es seguro repetirlo: no pisa lo editado.
 */
class SeedAstrofestEvent extends Command
{
    protected $signature = 'events:astrofest';

    protected $description = 'Crea el evento Astrofest Gemínidas 2026 si no existe';

    public function handle(): int
    {
        $tz = (string) config('academy.timezone', 'America/Mexico_City');
        $local = fn (string $d) => Carbon::parse($d, $tz)->timezone(config('app.timezone'));

        $event = Event::firstOrCreate(['slug' => 'astrofest-geminidas-2026'], [
            'name' => 'Astrofest Gemínidas 2026',
            'status' => Event::PUBLISHED,
            'start_at' => $local('2026-12-12 15:00'),
            'end_at' => $local('2026-12-13 13:00'),
            'countdown_from' => $local('2026-12-01 00:00'),
            'venue_name' => 'Club Campestre Teotihuacán',
            'venue_city' => 'Estado de México',
            'venue_address' => 'Autopista México–Tuxpan, salida km 27, 55851 San Martín de las Pirámides, Méx.',
            'price' => 1300,   // precio de 1 persona; los paquetes de abajo mandan
            'packages' => [
                ['key' => 'p1', 'name' => '1 persona', 'persons' => 1, 'price' => 1300, 'active' => true],
                ['key' => 'p2', 'name' => '2 personas', 'persons' => 2, 'price' => 2100, 'active' => true],
                ['key' => 'p3', 'name' => '3 personas', 'persons' => 3, 'price' => 2900, 'active' => true],
            ],
            'iva_rate' => 16,
            'max_per_order' => 10,
            'sales_enabled' => false,
            'hold_minutes' => 45,
            'offline_hold_hours' => 72,
            'discounts' => [
                'early_bird' => ['enabled' => false, 'label' => 'Preventa', 'percent' => 15, 'starts_at' => null, 'ends_at' => '2026-10-31'],
                'group' => ['enabled' => false, 'label' => 'Descuento por grupo', 'percent' => 10, 'min_qty' => 4, 'starts_at' => null, 'ends_at' => null],
            ],
            'content' => ['minors_age' => 6, 'noindex' => true],   // sin indexar hasta que el cliente apruebe
            'redirect_events_index' => true,
        ]);

        $this->info($event->wasRecentlyCreated ? 'Evento Astrofest creado (ventas apagadas hasta capturar políticas y activar).' : 'El evento Astrofest ya existía: no se modificó.');

        return self::SUCCESS;
    }
}
