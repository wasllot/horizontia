<?php

namespace App\Mail;

use App\Models\EventOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

    /**
     * Lo llama la cola cuando se agotan los intentos. Sin esto la compra seguiría marcada como «confirmación enviada»
     * (se marca al encolar) aunque el correo se haya perdido: se desmarca para que el panel lo muestre y «Reenviar» lo reintente.
     */
    public function failed(\Throwable $e): void
    {
        EventOrder::where('id', $this->order->id)->update(['confirmation_sent_at' => null]);
        Log::error('Evento: la confirmación al comprador no se pudo enviar', ['order' => $this->order->id, 'error' => $e->getMessage()]);
    }
}
