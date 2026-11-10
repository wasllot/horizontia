<?php

namespace App\Services\Events;

use App\Mail\EventHotelCodeEmail;
use App\Mail\EventHotelSummaryEmail;
use App\Models\Event;
use App\Models\EventOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Código de habitación de cada compra y aviso al hotel: al aprobarse un pago el hotel recibe el código con los datos de quien compró
 * (así valida la reservación y da el precio especial), y al cerrar las ventas recibe la lista completa.
 */
class EventHotelNotifier
{
    /** Alfabeto sin 0/O/1/I para que el código se pueda dictar por teléfono sin confusiones. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function newCode(): string
    {
        do {
            $code = 'HAB-' . $this->chunk() . '-' . $this->chunk();
        } while (EventOrder::where('hotel_code', $code)->exists());

        return $code;
    }

    private function chunk(): string
    {
        return substr(str_shuffle(str_repeat(self::ALPHABET, 2)), 0, 4);
    }

    /** Cada compra pagada recibe su código, salvo que el evento use un código general (entonces todos comparten ese). */
    public function assignCode(EventOrder $order): void
    {
        $order->loadMissing('event');
        if ($order->hotel_code || !$order->event->show('rooms') || $order->event->sharedHotelCode()) {
            return;
        }
        $order->update(['hotel_code' => $this->newCode()]);
    }

    /** El hotel recibe el código y los datos de la compra. Una sola vez por compra (se puede forzar desde el panel). */
    public function notify(EventOrder $order): bool
    {
        $order->loadMissing('event');
        $event = $order->event;
        $to = $event->hotelEmails();
        if (!$order->isPaid() || $order->hotel_notified_at || !$to || !$event->show('rooms') || !$order->roomCode()) {
            return false;
        }
        $claimed = EventOrder::where('id', $order->id)->whereNull('hotel_notified_at')->update(['hotel_notified_at' => now()]);
        if (!$claimed) {
            return false;
        }
        try {
            $mail = Mail::to($to);
            if ($event->contact_email && filter_var($event->contact_email, FILTER_VALIDATE_EMAIL)) {
                $mail->cc($event->contact_email);   // los organizadores ven lo mismo que el hotel
            }
            $mail->queue(new EventHotelCodeEmail($order->fresh(['event', 'tickets'])));

            return true;
        } catch (\Throwable $e) {
            EventOrder::where('id', $order->id)->update(['hotel_notified_at' => null]);
            Log::error('Evento: no se pudo avisar al hotel', ['order' => $order->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** Compras pagadas con código, de la más antigua a la más reciente. */
    public function orders(Event $event): Collection
    {
        return EventOrder::where('event_id', $event->id)->where('status', EventOrder::PAID)->with(['tickets', 'event'])->orderBy('paid_at')->orderBy('id')->get()
            ->filter(fn (EventOrder $o) => (bool) $o->roomCode())->values();
    }

    /** CSV para el hotel (UTF-8 con BOM para Excel). Las celdas que empiezan con = + - @ se neutralizan contra inyección de fórmulas. */
    public function csv(Event $event): string
    {
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Código de reservación', 'Titular', 'Correo', 'Teléfono', 'Personas', 'Paquetes', 'Asistentes', 'Pagado el (hora de México)']);
        foreach ($this->orders($event) as $o) {
            fputcsv($out, array_map($safe, [
                $o->roomCode(), $o->name, $o->email, $o->phone, $o->quantity, $o->packagesLabel(),
                $o->tickets->pluck('attendee_name')->implode('; '),
                $o->paid_at?->timezone(Event::timezone())->format('Y-m-d H:i'),
            ]));
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /** Lista completa al hotel (con el CSV adjunto). Devuelve false si no hay a quién ni qué enviar. */
    public function sendSummary(Event $event): bool
    {
        $to = $event->hotelEmails();
        if (!$to || !$event->show('rooms') || $this->orders($event)->isEmpty()) {
            return false;
        }
        $mail = Mail::to($to);
        if ($event->contact_email && filter_var($event->contact_email, FILTER_VALIDATE_EMAIL)) {
            $mail->cc($event->contact_email);
        }
        $mail->queue(new EventHotelSummaryEmail($event, $this->orders($event), $this->csv($event)));
        $event->forceFill(['hotel_summary_sent_at' => now()])->save();

        return true;
    }

    /** Eventos cuya venta ya cerró (o que ya terminaron) y cuyo hotel aún no recibe la lista final. @return int cuántos se enviaron */
    public function sendDueSummaries(): int
    {
        $sent = 0;
        foreach (Event::whereNull('hotel_summary_sent_at')->whereNotNull('hotel_email')->get() as $event) {
            $closed = ($event->sales_close_at && $event->sales_close_at->isPast()) || $event->isFinished();
            if ($closed && $this->sendSummary($event)) {
                $sent++;
            }
        }

        return $sent;
    }
}
