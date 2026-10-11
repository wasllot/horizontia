<?php

namespace App\Mail;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessRejectedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AccessRequest $request)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sobre tu solicitud de acceso');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.access-rejected');
    }
}
