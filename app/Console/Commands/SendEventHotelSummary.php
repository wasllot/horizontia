<?php

namespace App\Console\Commands;

use App\Services\Events\EventHotelNotifier;
use Illuminate\Console\Command;

/** Al cerrar las ventas de un evento (o al terminar), el hotel recibe la lista completa de códigos de reservación. Una sola vez por evento. */
class SendEventHotelSummary extends Command
{
    protected $signature = 'app:send-event-hotel-summary';

    protected $description = 'Envía al hotel la lista final de códigos de reservación de los eventos con ventas cerradas';

    public function handle(EventHotelNotifier $notifier): int
    {
        $sent = $notifier->sendDueSummaries();
        $this->info($sent . ' ' . ($sent === 1 ? 'lista enviada' : 'listas enviadas') . ' al hotel.');

        return self::SUCCESS;
    }
}
