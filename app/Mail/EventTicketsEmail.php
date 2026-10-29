<?php

namespace App\Mail;

use App\Models\EventOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Confirmación de compra de un evento: clave de acceso y QR por entrada, con el diseño del evento (no la plantilla de cursos). */
class EventTicketsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EventOrder $order)
    {
    }

    public function envelope(): Envelope
    {
        $event = $this->order->event;

        return new Envelope(
            subject: 'Tus entradas para ' . $event->name . ' ' . ($this->order->quantity > 1 ? '(' . $this->order->quantity . ')' : ''),
            replyTo: $event->contact_email ? [$event->contact_email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.events.tickets', with: ['order' => $this->order, 'event' => $this->order->event, 'tickets' => $this->order->tickets]);
    }
}
