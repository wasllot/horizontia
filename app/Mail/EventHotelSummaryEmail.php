<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** Lista final al hotel al cerrar las ventas: todos los códigos de reservación con quién compró (también va como CSV adjunto). */
class EventHotelSummaryEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Event $event, public Collection $orders, public string $csv)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->event->name . ' · Lista final de códigos de reservación (' . $this->orders->count() . ')',
            replyTo: $this->event->contact_email ? [$this->event->contact_email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.events.hotel-summary', with: ['event' => $this->event, 'orders' => $this->orders]);
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->csv, 'codigos-hotel-' . $this->event->slug . '.csv')->withMime('text/csv')];
    }
}
