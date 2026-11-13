<?php

namespace App\Mail;

use App\Models\EventOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Aviso al hotel: código de reservación de una compra pagada y los datos de quien compró, para dar el precio especial. */
class EventHotelCodeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EventOrder $order)
    {
    }

    public function envelope(): Envelope
    {
        $event = $this->order->event;

        return new Envelope(
            subject: $event->name . ' · Código de reservación ' . $this->order->roomCode() . ' · ' . $this->order->name,
            replyTo: $event->contact_email ? [$event->contact_email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.events.hotel-code', with: ['order' => $this->order, 'event' => $this->order->event, 'tickets' => $this->order->tickets]);
    }

    /** Igual que el correo del comprador: si la cola lo pierde, el aviso deja de figurar como enviado. */
    public function failed(\Throwable $e): void
    {
        EventOrder::where('id', $this->order->id)->update(['hotel_notified_at' => null]);
        Log::error('Evento: el aviso al hotel no se pudo enviar', ['order' => $this->order->id, 'error' => $e->getMessage()]);
    }
}
