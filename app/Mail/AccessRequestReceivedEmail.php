<?php

namespace App\Mail;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessRequestReceivedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AccessRequest $request)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recibimos tu solicitud de acceso');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.access-received');
    }
}
